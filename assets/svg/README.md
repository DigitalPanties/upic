# SVG code registry

All SVG definitions are MIT-licensed source code, not separately licensed artwork.

- `registry.json` is the authoritative mapping of hexadecimal IDs to component meanings.
- `cuts/*.svg` contains front/back SVG templates, one file per registered cut.
- `patterns/*.svg` contains existing print patterns in standalone SVG documents.
- `fabrics/`, `trim_materials/`, `edge_finishes/` and `details/` are component families with registered numeric IDs; their current rendering is procedural in PHP and any new standalone SVG needs a renderer integration.

**Edit source SVG → edit registry → compile → check:**

```bash
python3 tools/svg_registry.py build
python3 tools/svg_registry.py check
php tests/registry_contract.php
php tests/run.php
```

The PHP runtime uses the compiled `assets/artwork.json` for original compatibility; do not edit that file manually. Read [`SVG_SPEC.md`](../../SVG_SPEC.md) for geometry, security, compatibility, contribution, and license requirements.
