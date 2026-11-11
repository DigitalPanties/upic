# Change log

## 1.0.0-rc.3 — Panty Maker v1.17 pattern references (2026-11-11)

- Registered five additional canonical print IDs: `0D` Leopard, `0E` Lace look, `0F` Stars, `10` Camouflage and `11` Plaid.
- Replaced provisional RC2 `0B`/`0C` florals with exact source SVG patterns from Panty Maker v1.17. The IDs and encoded bytes did not change, but the provisional RC2 floral renderings have changed.
- Imported the repaired, balanced `<pattern>` SVG markup and pinned SHA-256 hashes from the Maker v1.17 source.
- Updated demo selector, consumer decoder, HTML gallery, SVG registry build, and tests across 18 prints, 10 cuts and both sides.
- No change to the nine-byte core, Base64 alphabet, TLV records or CRC32. Candidate only; not yet published as a GitHub release.

## 1.0.0-rc.2 — Floral patterns registry update (2026-11-11)

- Added experimental hexadecimal pattern references `0B` Small floral and `0C` Large floral in the original pattern slot.
- Added individually maintained SVG source for each motif, rebuilt renderer assets, updated designer selector and optional Style Brief decoder.
- Added front/back round-trip and preservation regression for original codes.
- **Verification still required** against Panty Maker v1.15 source to confirm both reference numbers and pixel geometry before official release.
- Additional prints were pending verification in RC2 and are registered in RC3.

## 1.0.0-rc.1 — Proposed public baseline (2026-11-11)

- Designated the current nine-byte Maker-compatible core and appended positional-TLV extension grammar as the proposed UPIC 1.0 public specification.
- Documented the distinction between normative formats and migration-only experimental drafts.
- Added stable-wire regression checks without changing any existing encoding or rendering behaviour.
- Explicitly separated the canonical UPIC release/version from Panty Maker application releases.

## Unreleased — 2026-11-11

- Fixed original-core preservation when an extended design carries a full 24-bit RGB override that differs from its legacy `#RGB` base slot. Re-encoding a decoded code preserves the original 12-character prefix (and validates consistency of its cut, pattern and sheer fields).
- Added Maker v1.9 interoperability regression using an actual code from its codec; the upstream PHP renderer now accepts the code without changing its binary format.
- Added optional standalone UPIC Style Brief example, with client-only decoder and Node tests, without changing the normative protocol.
- Confirmed Maker v1.11 figure alignment and visual controls belong to Maker, not the shared UPIC codec.

## Unreleased — 2026-11-10

- Original Panty Maker cut and pattern SVGs included as first-class MIT-licensed code, with the original creator's authorization.
- New hexadecimal SVG registry covering cuts, prints, fabrics, trim materials, edge finishes and details.
- Individual source SVG files for ten original cuts and nine original SVG patterns; two additional hearts remain procedural and registered.
- Added `SVG_SPEC.md`, `GOVERNANCE.md`, an SVG build/validator, contributor templates, and automated registry checks in GitHub Actions.
- Preserved byte-identical original SVG rendering, historical design-code layout, and all existing 772 regression checks.
- Original UPIC source and contour-based trim expansion retained from the preceding implementation.
