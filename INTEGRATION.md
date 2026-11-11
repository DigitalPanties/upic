# UPIC / Panty Maker integration boundary

The UPIC library is **the specification and reference implementation**. Panty Maker is a consumer of it, not the canonical source of reference numbers. Maintain them independently.

## Source reconciliation (11 November 2026)

Inputs reviewed:

1. `UPIC_Complete_GitHub_Repo_FRESH.zip` — MIT repository, canonical registry, original cut/pattern SVGs and contour profiles.
2. `UPIC_Engine_Candidate_1_9.zip` — newer Maker-oriented PHP/JS codec and rendering candidate (not a standalone upstream patch; depends on `pm_svg()` / `pm_color()`).
3. `Panty_Maker_v1_11_Figure_Alignment_Fix.zip` — more recent Maker application, whose combined installation contains the Maker engine but also independent visual, figure and layout fixes.
4. `UPIC_Style_Brief_Prototype_2026-11-11.zip` — browser-only consumer of UPIC codes.

What was ported into the reference implementation:

- Compatibility with the Maker sample code `D0gJD8|||5sEAQECEAcBAgED|3yrIAYBAQP|||8wBAH|RGbiAFu7`. Its legacy prefix `D0gJD8|||5sE` must remain unchanged despite the full-RGB colour override.
- The core colour/override separation and round-trip regression test.
- The Style Brief application under `examples/style-brief/` (optional consumer; not a new field family).

What was **not** copied into the UPIC core:

- The Maker-specific `lib/core.php` helper or dependent `lib/upic_render.php`; overwriting `src/UPIC.php` would regress the canonical SVG registry and high-precision edge profiles.
- Maker HTML/CSS/background image alignment, panty-body overlay and application deployment files; those remain owned by Panty Maker.
- The lower-precision `lib/edge_profiles.json` from the Maker candidate. UPIC's registered profiles and SVG reference contract remain authoritative.

## Updating an actual GitHub repository

Before publishing, compare this source tree with the remote repository's current `main`. Do not force-push or overwrite contributions. Create a feature branch, commit the changes, run PHP/Node/SVG validation in CI, review the diff and merge. This package by itself does not update a live GitHub repository.

## Cross-implementation compatibility checks

```sh
php tests/run.php
php tests/registry_contract.php
php tests/maker_interop.php
python3 tools/svg_registry.py check
node examples/style-brief/tests/test.js
```

If third-party implementations generate syntactically correct but noncanonical byte order, they should be corrected rather than causing the official registry to assign different meanings to existing bytes. An existing core and its full-colour overrides may intentionally differ, however; those two values are not a noncanonical representation by themselves.

### Common print registry (UPIC 1.0 RC3)

Panty Maker v1.17 provides the verified SVG source for `0B`–`11`. These references are now stable in the canonical UPIC registry. Both florals (`0B`/`0C`) preserve the previously allocated numeric IDs but use the corrected Maker v1.17 geometry. The UPIC renderer and other applications should read these definitions from the shared registry rather than maintain divergent copies.
