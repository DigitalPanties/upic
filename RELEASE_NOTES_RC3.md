# UPIC 1.0.0-rc.3 — Maker 1.17 patterns sync

**Status:** GitHub-ready release candidate. This archive has not been pushed to a remote repository or released as a final specification.

## Registered print references

The original print reference field stays at its original byte position. Added canonical Maker v1.17 definitions:

- `0B` Small floral (replaces provisional rc.2 SVG geometry)
- `0C` Large floral (replaces provisional rc.2 SVG geometry)
- `0D` Leopard
- `0E` Lace look
- `0F` Stars
- `10` Camouflage
- `11` Plaid

Original `00`–`0A` references, legacy twelve-character codes and append-only extension grammar are unchanged. All imported SVG pattern markup matches the repaired Maker 1.17 `lib/artwork.php` source; pinned hashes are under `tests/fixtures/`. The optional Style Brief recognizes every print.

## Verifying

```sh
python3 tools/svg_registry.py check
php tests/registry_contract.php
php tests/run.php
php tests/floral_patterns.php
php tests/common_patterns.php
python3 tests/rendered_svg_xml.py
php tests/maker_interop.php
php tests/baseline_wire.php
node examples/style-brief/tests/test.js
```

Open `examples/common-patterns/index.html` to inspect front/back reference renders for all seven Maker 1.17 patterns.

## Integration note

UPIC defines codes and SVG registry. Maker-specific UI and Panty Lane drawer/ownership systems remain separate applications. An unchanged reference ID now uses the verified upstream geometry; its byte string remains the same. Review existing visual snapshots for `0B` and `0C`, whose provisional RC2 artwork differed.
