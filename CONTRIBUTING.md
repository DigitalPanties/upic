# Contributing to UPIC

**SVGs are source code.** The project's original cut SVGs and patterns are released under MIT alongside the renderer. Changes to them receive the same code review, test, and compatibility scrutiny as PHP changes.

## Typical contribution

1. Open an issue describing the new cut, pattern, fabric, trim, edge or detail—or an improvement to an existing definition.
2. Fork the repository and create a branch. Check `assets/svg/registry.json` to avoid taking an existing numeric hex reference.
3. For a **new cut**, add `assets/svg/cuts/<HEX>-name.svg`, register it, provide both side-specific contour profiles and decorative anchors, and add a rendering test. For a **new print**, add `assets/svg/patterns/<HEX>-name.svg`, register it and add a front/back rendering test.
4. For **fabrics, trim materials, edge finishes or details**, add/reference an SVG in the matching registry family if needed. These families are procedural today: also implement renderer support for any new SVG-driven rendering.
5. Run `python3 tools/svg_registry.py build` to update the compiled renderer data (never hand-edit `assets/artwork.json`). Then run all checks listed below.
6. Open a pull request with encoded sample IDs, front/back screenshots and a before/after comparison if altering a stable reference.

## Tests and source validation

```bash
python3 tools/svg_registry.py check
php tests/registry_contract.php
php tests/run.php
find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;
```

The Python validator uses only standard-library packages. `tools/generate_contours.py` optionally uses `fonttools` and `shapely` for source geometry preparation; its generated output must still be reviewed.

## Compatibility checklist

- The original nine bytes and 12-character code retain their exact position, colour, and cut/pattern meaning.
- Existing hexadecimal reference values are never repurposed. Add a new number for a new meaning.
- All SVGs pass [`SVG_SPEC.md`](SVG_SPEC.md). No external assets, scripts, hidden metadata, or user-provided active markup.
- Edge and detail positions remain correct for both front and back.
- Every new reference has a real renderable implementation; don't just encode an undocumented reference.
- The contributor owns the submission or has rights to release it under MIT. No unauthorized third-party artwork.

See [GOVERNANCE.md](GOVERNANCE.md) for the review/merge policy. Independent forks are allowed by MIT, but their unmerged reference IDs must be described as fork-specific and may not be interoperable with other forks. Security issues should go privately through [SECURITY.md](SECURITY.md), not public issues.
