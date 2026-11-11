# UPIC SVG-as-Code Specification

**Status:** repository contract, 2026-11-10. **License:** MIT, including the original Panty Maker SVG source code. See `LICENSE`.

The original SVG cuts and patterns were written by the project's original creator, who has authorized their release **as source code** alongside the encoder and renderer under the repository's MIT license. An SVG here is a versioned rendering component: its **numeric reference** is part of the protocol, while its shape and styling implementation can improve over time.

## 1. Sources of truth

- `assets/svg/registry.json` maps canonical **hexadecimal references** to a name, status, and renderer source for each family.
- `assets/svg/cuts/NN-description.svg` and `assets/svg/patterns/NN-description.svg` are first-class, human-editable vector source files.
- `assets/artwork.json` is the **compiled compatibility artifact** consumed by the current PHP renderer. Do not edit it by hand. Use `python3 tools/svg_registry.py build`, then commit the result.
- `assets/edge_profiles.json` contains the placement contours for cut-specific trims; `assets/cut_anchors.json` contains cut-specific decorative anchors. Both are version-controlled code/data and covered by MIT.
- The current fabric, trim, edge, and detail definitions are procedural implementations in `src/UPIC.php`; all of their numeric identities are also registered in `assets/svg/registry.json`. Future SVG definitions in these families follow the same validation rules, but require a matching renderer integration—merely registering an SVG file does **not** make the current PHP renderer consume it.

## 2. Hex reference ownership

Each family has its **own independent reference space**. `02` in fabrics is satin, whereas `02` in trim materials is lace. The code field and placement determine which registry is used.

- Original cut IDs `01`–`0A` and pattern IDs `00`–`08` are frozen in meaning. Pattern IDs `09` and `0A` are built-in small and large hearts.
- New cut/pattern IDs use available two-digit hex references first (`0B`, `0C`, etc.). Higher-order references are allowed by the existing UPIC extension protocol, as documented in `PROTOCOL.md`.
- Use **uppercase, even-width** hex keys in the registry (`0A`, `FF`, `0100`). The same numeric reference must never appear twice within one family, including differently padded spellings.
- Do not reuse, reinterpret, or delete an assigned stable ID. Deprecate by marking and preserving compatibility rather than reassignment; schema changes that would invalidate issued codes require separate protocol review.
- **Upstream merges allocate official values.** A fork may create its own registrations, but any conflicting or fork-only values must be clearly identified as fork-specific. The current code does not encode a vendor namespace, so references independently allocated by forks are **not globally interoperable**. Fork authors should publish the registry revision with their distribution and reconcile conflicts before submitting upstream.

## 3. Shared SVG file contract

All committed SVG source files, for any family, must:

1. Be standalone, well-formed UTF-8 XML in the `http://www.w3.org/2000/svg` namespace, with root `<svg>` and viewBox **`0 0 69.21 69.21`**.
2. Use deterministic vector elements without external URLs, image links, event handlers, scripts, foreign objects, inline CSS, or embedded executable content. Sanitization/validation runs on every PR.
3. Live at `assets/svg/<family>/<HEX>-descriptive-name.svg`, match a unique `registry.json` entry, and stay under the source size limit (currently 256 KB per file).
4. Use stable internal IDs and renderer-compatible colour placeholders. The cut and pattern requirements below are specific to the current PHP renderer. No remote fonts or other runtime assets are required.
5. Provide front/back screenshots or SVG renders and a compatibility explanation when editing stable geometry. A visual change to an existing reference is an implementation update, **not a new semantic identity**.

### Cut templates (`assets/svg/cuts/`)

- **Exactly one** `<path class='Front' ... d='...'>` and **one** `<path class='Back' ... d='...'>` per cut, in the shared coordinate system.
- Use `class='Front'` / `class='Back'`, a single-quoted `d` attribute, and the existing `fill='rgba(255,122,122,255)'` front / `fill='rgba(255,122,255,255)'` back placeholders. These details are intentional compatibility points in `src/UPIC.php`.
- Keep the exact `<defs><style></style></defs>` insertion point. The renderer adds patterns and side-specific visibility there.
- For every registered cut, provide both sides' `waist`, `left_leg`, `right_leg` contours in `assets/edge_profiles.json` and `trimTop` / `detailY` in `assets/cut_anchors.json`.
- The contour generator `tools/generate_contours.py` is optional and uses `fonttools` and `shapely`. Review its output before committing: generated normals, side mapping, and placement matter for lace/scallops.
- A cut can have an updated silhouette while keeping its reference if it remains the **same cut type**. Fundamentally different cuts receive a new reference.

### Pattern definitions (`assets/svg/patterns/`)

- Define a single `<pattern id='ptrn' ...>...</pattern>` or equivalent double-quoted ID inside a single `<defs>...</defs>` element; the source is a complete SVG document.
- The build tool extracts the **exact original pattern string** between those markers and compiles it into `assets/artwork.json` without XML reserialization.
- Where legacy colour substitution applies, use `rgb(66,66,66)` for print foreground and `rgb(255,255,255)` for underlying colour. New patterns should follow these placeholders until the renderer supports a more expressive colour contract.
- The `09` and `0A` heart prints currently come from PHP built-ins, not SVG files. A future refactor may move them to registered SVG files if their rendered outputs and existing numeric meanings are preserved.

### Other vector families

| Family | What the reference identifies | Current rendering approach |
| --- | --- | --- |
| `fabrics` | Garment-wide material/texture | Procedural SVG fill/gradient |
| `trim_materials` | Material added at an edge | Procedural along registered contours |
| `edge_finishes` | Scallop, picot, eyelash, etc. | Procedural along registered contours |
| `details` | Bow, heart, rosette, etc. | Procedural at cut-specific anchors |

For new SVG-driven versions, add a registry record and the SVG file, implement its use in the renderer, and prove that placement and colour work without changing older codes. SVG components for an edge are typically motifs projected along a cut contour, **not** whole-garment geometry. The reference distinguishes *what it is*; the positional code supplies *where it goes*.

## 4. Mandatory PR checks

```bash
python3 tools/svg_registry.py check
php tests/registry_contract.php
php tests/run.php
```

If SVG source files change, run `python3 tools/svg_registry.py build` and commit any regenerated `assets/artwork.json`. CI rejects stale compiled files, orphan SVGs, malformed definitions, missing cut contours, conflicting reference numbers, and known unsafe SVG elements/attributes. This is a repository-level safeguard, **not** a general-purpose sanitizer for arbitrary untrusted SVG uploads; do not render raw uploads.

A PR adding a cut or print must include a real encoded example and both front/back visual checks. A PR changing an existing cut or print must include before/after images and demonstrate unchanged reference semantics. Preserve `tests/legacy_reference.svg` as the golden output unless a separately approved compatibility change explicitly supersedes it.

## 5. License and forks

All original and contributed SVG source files, JSON vector definitions, profiles, anchors, implementation code, tests, and generated examples in this repository are licensed under **MIT**. Contributors must own or have permission to contribute all submitted material under MIT. Retain the license and copyright notice when redistributing substantial portions. Forking, modification, and commercial reuse are allowed by the license; merging into the canonical project is subject to maintainer review, not automatic. The **Panty Lane name and marks** are not licensed merely because source code is open.

## Maker-verified common print definitions (RC3)

`0B` Small floral, `0C` Large floral, `0D` Leopard, `0E` Lace look, `0F` Stars, `10` Camouflage and `11` Plaid are stable numeric references. The `<pattern>` markup embedded in their source SVGs is byte-for-byte identical to Panty Maker v1.17 `lib/artwork.php`; hashes are recorded in `tests/fixtures/maker_v1_17_patterns.json`.

These are source-code elements, not separately licensed artwork. Once registered, IDs must not be reassigned. Geometry changes require code review, both-side examples, and documentation of visual changes to previously issued codes.
