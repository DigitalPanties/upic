#!/usr/bin/env python3
"""Validate SVG-as-code registry and compile source SVGs to the legacy renderer's JSON.

Usage: python3 tools/svg_registry.py check | build
No dependencies outside the Python standard library; intended to run in GitHub CI.
"""
from __future__ import annotations
import json
import pathlib
import re
import sys
import xml.etree.ElementTree as ET

ROOT = pathlib.Path(__file__).resolve().parent.parent
BASE = ROOT / 'assets/svg'
REGISTRY = BASE / 'registry.json'
ART = ROOT / 'assets/artwork.json'
NS = 'http://www.w3.org/2000/svg'
VIEWBOX = '0 0 69.21 69.21'
FAMILIES = ('cuts', 'patterns', 'fabrics', 'trim_materials', 'edge_finishes', 'details')
ALLOWED = {'svg', 'g', 'defs', 'style', 'path', 'rect', 'circle', 'ellipse', 'polygon', 'polyline',
           'line', 'pattern', 'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask'}
# Reject scripting, external resource loading, CSS, and event handlers in contributed vector code.
FORBIDDEN_TOKENS = (b'<!doctype', b'<!entity', b'<script', b'<foreignobject', b'javascript:',
                    b'@import', b'url(', b'<?xml-stylesheet')
EMPTY_TEMPLATE = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 69.21 69.21'></svg>"


def fail(message: str):
    raise ValueError(message)


def load_json(path: pathlib.Path):
    def unique(pairs):
        obj = {}
        for key, value in pairs:
            if key in obj:
                fail(f'{path}: duplicate object key {key!r}')
            obj[key] = value
        return obj
    return json.loads(path.read_text(encoding='utf-8'), object_pairs_hook=unique)


def read_svg(filename: str, family: str) -> str:
    if not isinstance(filename, str) or not re.fullmatch(r'[a-z_]+/[0-9A-F]{2,8}-[a-z0-9-]+\.svg', filename):
        fail(f'Bad SVG path: {filename!r}')
    path = (BASE / filename).resolve()
    if not path.is_relative_to(BASE.resolve()) or not path.is_file():
        fail(f'SVG is missing or outside registry: {filename}')
    if path.stat().st_size > 256_000:
        fail(f'SVG too large: {filename}')
    data = path.read_bytes()
    if any(s in data.lower() for s in FORBIDDEN_TOKENS):
        fail(f'Unsafe SVG feature in {filename}')
    try:
        tree = ET.fromstring(data)
    except ET.ParseError as ex:
        fail(f'SVG XML invalid at {filename}: {ex}')
    if tree.tag != f'{{{NS}}}svg':
        fail(f'{filename}: root must be an SVG in the SVG namespace')
    if tree.get('viewBox') != VIEWBOX:
        fail(f'{filename}: viewBox must be {VIEWBOX}')
    for el in tree.iter():
        if not el.tag.startswith('{' + NS + '}') or el.tag.split('}')[-1] not in ALLOWED:
            fail(f'{filename}: disallowed SVG element {el.tag}')
        if el.tag == f'{{{NS}}}style' and (el.text or '').strip():
            fail(f'{filename}: inline CSS is not allowed')
        for attr, value in el.attrib.items():
            key = attr.split('}')[-1].lower()
            if (attr.startswith('{') or key.startswith('on') or key in ('href', 'style', 'src') or
                re.search(r'(?i)(?:https?://|data:|javascript:|url\s*\()', value)):
                fail(f'{filename}: unsafe/external SVG attribute {attr}')
    if family == 'cuts':
        for side in ('Front', 'Back'):
            elements = [el for el in tree.iter() if el.tag == f'{{{NS}}}path' and el.get('class') == side]
            if len(elements) != 1 or not elements[0].get('d'):
                fail(f'{filename}: needs exactly one nonempty {side} path')
        if not re.search(r"<path class='Front'[^>]+ d='[^']+'", data.decode('utf-8')) or not re.search(r"<path class='Back'[^>]+ d='[^']+'", data.decode('utf-8')):
            fail(f"{filename}: use path class='Front' / class='Back' and a single-quoted d attribute (renderer contract)")
        if (b"fill='rgba(255,122,122,255)'" not in data or
            b"fill='rgba(255,122,255,255)'" not in data):
            fail(f'{filename}: front and back need the canonical renderer colour placeholders')
        if b'<defs><style></style></defs>' not in data:
            fail(f'{filename}: renderer requires empty <defs><style></style></defs> insertion slot')
    elif family == 'patterns':
        defs = [el for el in tree if el.tag == f'{{{NS}}}defs']
        nodes = list(defs[0]) if len(defs) == 1 else []
        if len(nodes) != 1 or nodes[0].tag != f'{{{NS}}}pattern' or nodes[0].get('id') != 'ptrn':
            fail(f'{filename}: <defs> must contain one <pattern id="ptrn">')
        if data.count(b'<defs>') != 1 or data.count(b'</defs>') != 1:
            fail(f'{filename}: exact <defs> marker required for lossless legacy SVG extraction')
    return data.decode('utf-8').rstrip('\n')


def validate_geometry(cuts):
    anchors = load_json(ROOT / 'assets/cut_anchors.json')
    profiles = load_json(ROOT / 'assets/edge_profiles.json')
    for ref in cuts:
        numeric = str(int(ref, 16))
        if numeric not in anchors or numeric not in profiles:
            fail(f'Cut {ref}: register detail anchors and front/back edge profiles')
        for attr in ('detailY', 'trimTop'):
            if not isinstance(anchors[numeric].get(attr), (int, float)):
                fail(f'Cut {ref}: missing numeric anchor {attr}')
        for side in ('front', 'back'):
            for zone in ('waist', 'left_leg', 'right_leg'):
                profile = profiles[numeric].get(side, {}).get(zone)
                if not profile or profile.get('outward') not in (-1, 1):
                    fail(f'Cut {ref} {side}/{zone}: missing contour or outward direction')
                points = profile.get('points')
                if not isinstance(points, list) or len(points) < 2 or any(
                    not isinstance(pt, list) or len(pt) != 2 or any(type(v) not in (float, int) for v in pt)
                    for pt in points
                ):
                    fail(f'Cut {ref} {side}/{zone}: invalid contour points')


def process():
    registry = load_json(REGISTRY)
    if registry.get('schema_version') != 1 or registry.get('viewBox') != VIEWBOX:
        fail('Unsupported registry schema or canvas')
    if not all(isinstance(registry.get(f), dict) for f in FAMILIES):
        fail('Every SVG registry family must be present')
    referenced = set()
    compiled = {'viewBox': VIEWBOX, 'templates': {'0': EMPTY_TEMPLATE}, 'patterns': {}, 'names': {}}
    for family in FAMILIES:
        used_ids = set()
        for ref, item in registry[family].items():
            if not re.fullmatch(r'(?:[0-9A-F]{2}){1,4}', ref):
                fail(f'{family}: reference must be 2/4/6/8 uppercase hexadecimal digits: {ref}')
            num = int(ref, 16)
            if num in used_ids or ref != format(num, '0' + str(max(2, (num.bit_length()+7)//8 * 2)) + 'X'):
                fail(f'{family}: ambiguous or duplicated reference {ref}')
            used_ids.add(num)
            if not isinstance(item, dict) or not isinstance(item.get('name'), str) or not item['name']:
                fail(f'{family}:{ref}: missing name')
            if item.get('status') not in ('stable', 'experimental'):
                fail(f'{family}:{ref}: missing stable/experimental status')
            renderer = item.get('renderer')
            filename = item.get('file')
            if family == 'cuts' and renderer != 'template':
                fail(f'{family}:{ref}: cuts must use a template SVG')
            if family != 'cuts' and renderer not in ('svg', 'builtin'):
                fail(f'{family}:{ref}: renderer must be svg or builtin')
            if (renderer == 'builtin') != (filename is None):
                fail(f'{family}:{ref}: builtin refs cannot have an SVG file, SVG refs require one')
            if filename is not None:
                if not filename.startswith(('cuts/' if family == 'cuts' else 'patterns/' if family == 'patterns' else family + '/')):
                    fail(f'{family}:{ref}: SVG in wrong family directory')
                if not filename.split('/')[-1].startswith(ref + '-'):
                    fail(f'{family}:{ref}: filename must begin with its hex ID')
                if filename in referenced:
                    fail(f'{family}:{ref}: file registered more than once')
                referenced.add(filename)
                svg = read_svg(filename, family)
                if family == 'cuts':
                    compiled['templates'][str(num)] = svg
                    compiled['names'][str(num)] = item['name']
                if family == 'patterns':
                    compiled['patterns'][str(num)] = svg.split('<defs>', 1)[1].split('</defs>', 1)[0]
            if family == 'patterns' and renderer == 'builtin' and num not in (9, 10):
                # Additional procedural patterns require a corresponding renderer implementation review.
                fail(f'Pattern {ref}: only builtin hearts 09 and 0A are implemented')
    unregistered = {str(f.relative_to(BASE)) for f in BASE.rglob('*.svg')} - referenced
    if unregistered:
        fail(f'Unregistered SVG source files: {sorted(unregistered)}')
    validate_geometry(registry['cuts'])
    return compiled, registry


def main():
    action = sys.argv[1] if len(sys.argv) > 1 else 'check'
    if action not in ('check', 'build'):
        print('Usage: python3 tools/svg_registry.py [check|build]', file=sys.stderr)
        return 2
    try:
        compiled, registry = process()
        current = load_json(ART)
        if action == 'check' and compiled != current:
            fail('Compiled assets/artwork.json differs from the registered SVG files. Run: python3 tools/svg_registry.py build')
        if action == 'build' and current != compiled:
            # JSON object keys order is not significant. Preserve text inside original SVG strings.
            ART.write_text(json.dumps(compiled, separators=(',', ':'), ensure_ascii=False) + '\n', encoding='utf-8')
        counts = ', '.join(f'{f}={len(registry[f])}' for f in FAMILIES)
        print(f'SVG registry {action}: OK ({counts}; {len(list(BASE.rglob("*.svg")))} SVG sources)')
        return 0
    except (ValueError, OSError) as ex:
        print(f'SVG registry {action}: FAIL: {ex}', file=sys.stderr)
        return 1


if __name__ == '__main__':
    sys.exit(main())
