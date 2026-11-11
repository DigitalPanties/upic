# Universal Panty Identification Code (UPIC)

**Positional hexadecimal protocol, contour-based expansion (10 November 2026).** This is the Panty Lane design-code specification, not an external standard. The code identifies what to render, not who owns it.

## Non-negotiable compatibility

1. The 9-byte **original Panty Maker core** never moves or changes meaning.
2. A legacy 9-byte design keeps its **exact original 12-character ID**. No code prefix (`UP1-` or otherwise) and no redundant version byte.
3. New attributes are appended after those nine bytes, as numeric hexadecimal reference bytes. The first 12 characters of the new base64-encoded ID are the **unchanged original code**.
4. Pre-existing uses of the core's two-hex-digit cut and pattern slots are preserved. New registered cuts/patterns consume available values *in those slots first*; only reference values greater than `FF` require high-order extension bytes.
5. `#RGB` core colour fields are unchanged. Full 6-digit hex colours use optional exact-colour TLVs; these overrides do not require rewriting the original core colour nibbles.
6. A numbered attribute selects a registered rendering definition. Unknown references can be encoded, decoded and preserved, but are not fabricated visually.
7. Once issued, the original colour slots also remain unchanged when a six-hex-digit colour override is added. The full-colour override affects rendering but must **not** silently re-quantize the original nine-byte core. Decoded designs expose `coreColors` separately from effective `colors`.

## Original 9-byte core

The original 18 hexadecimal digits (9 bytes), left to right:

| Zero-based hex positions | Hex size | Field |
|---|---:|---|
| `0` | 1 | Original reserved nibble `0` |
| `1..3` | 3 | Pattern colour `RGB` |
| `4..5` | 2 | Pattern reference (`00`–`FF`) |
| `6` | 1 | Sheerness (`0`–`F`) |
| `7..9` | 3 | Main trim colour `RGB` |
| `10..12` | 3 | Back colour `RGB` |
| `13..15` | 3 | Front colour `RGB` |
| `16..17` | 2 | Cut reference (`01`–`FF`) |

Original cut values `01`–`0A`: Boyshort, Control High-Waist, Hipster, Cheeky Brazilian, Bikini, Slip, Tanga, String Bikini, Thong, G-string. Values `0B` and above are available for registering additional cuts.

Pattern values `00`–`08` preserve original Solid, Small/Large Dots, Small/Large Check, Narrow Horizontal/Vertical Stripes, Wide Horizontal/Vertical Stripes. `09`/`0A` are registered in the **original pattern field** as Small Hearts and Large Hearts. No separate heart selector is needed. Registered patterns `0B` Small floral, `0C` Large floral and `0D`–`11` common prints use these core two-digit slots; the SVGs are verified against Panty Maker v1.17.

The 9-byte core is encoded in the Maker's modified Base64 alphabet: `+` becomes `*`, `/` becomes `|`. No padding for extended codes. The original 9 bytes produce exactly 12 characters.

## Extended binary layout

```text
[ original 9 bytes ]
[ tag: 1 byte ][ length: 1 byte ][ value: length bytes ]
[ another tag/length/value if required ... ]
[ CRC32 of all preceding bytes: 4 bytes, big-endian ]
```

No extension records → use **only the original 9 bytes**, no CRC. CRC is accidental-corruption detection, not authenticity or ownership verification. Extended payload limit: 512 bytes, inclusive. The first twelve Base64 characters are preserved because nine bytes align on a Base64 triplet boundary.

### Attribute tags and field semantics

All reference values are binary unsigned integers displayed here in hexadecimal; they are **not textual strings in the encoded code**.

| Tag | Bytes | Meaning |
|---|---:|---|
| `01` | 1 | Fabric reference (registered, omit if unspecified) |
| `10` | 7 | **Base trim layer 0:** `zone:1 material:1 edge:1 width:1 RGB:3` |
| `11` | 8 | **Additional trim layer 1–3:** `zone:1 layer:1 material:1 edge:1 width:1 RGB:3` |
| `20` | 6 | Detail: `detail-ref:1 placement:1 size:1 RGB:3` |
| `30` | 4 | Exact colour override: `original-colour-part:1 RGB:3` |
| `41` | 1–3 | High-order bytes of cut reference when cut exceeds `FF` |
| `42` | 1–3 | High-order bytes of pattern reference when pattern exceeds `FF` |

Example: cut `010B` uses `0B` in the original cut byte, then a `41 01 01` record for the high-order byte `01`. Same for pattern. Unknown optional tag numbers in `02..7F` are retained byte-for-byte after canonicalization. Unknown critical tags `80..FF` are rejected. `00` is reserved; `40` remains a compatibility-only pattern tag for the earlier F1 package.

### Registered reference values

| Family | Reference values (`hex`) |
|---|---|
| Fabric (`01`) | `00` unspecified; `01` cotton; `02` satin; `03` silk; `04` nylon; `05` mesh; `06` lace fabric; `07` microfiber; `08` stretch lace; `09` chiffon; `0A` jersey; `0B` velvet |
| Edge zones (`10`/`11`) | `01` waist; `02` left leg; `03` right leg; `04` all edges |
| Trim materials | `00` plain; `01` elastic; `02` lace; `03` ribbon; `04` piping; `05` stitch; `06` crochet; `07` mesh; `08` satin binding; `09` beaded |
| Edge finish | `00` smooth; `01` scalloped; `02` picot; `03` wavy; `04` ruffled; `05` zigzag; `06` eyelash fringe |
| Detail references | `01` bow; `02` rosette; `03` heart; `04` side tie; `05` pearl; `06` flower appliqué |
| Detail positions | `01` front centre; `02` left hip; `03` right hip; `04` back centre |
| Exact-colour parts | `01` front; `02` back; `03` main trim; `04` print/pattern |

**Important distinctions:** `fabric=06` (the entire base garment is lace), `trim.material=02` (lace attached to an edge), and `trim.edge=01` (scalloped border) are independent. A lace-trimmed garment may have smooth edging; a plain elastic trim may have scalloped edges. Placement and RGB values distinguish matching materials applied to different garment regions.

Width `01..05` is a visual styling scale, not physical millimetres. Layer 0 is the original `10` format with no layer byte. Layers 1–3 use `11` and have a layer byte. Each `(zone, layer)` pair is unique. More than one material/finish at one zone is supported using separate layers. `all_edges` is a default for that layer; a specific waist or leg declaration at the **same layer** overrides it in rendering, while layers independently stack.

### Canonical record order

High-order cut, high-order pattern, fabric, trims in ascending `(zone, layer)` order, details in byte order, exact-colour overrides in base-part order, unknown optional tags in original order. Duplicate singleton records and duplicate `(zone, layer)` pairs are rejected. Invalid sizes/widths/CRC are rejected. This supports deterministic output and lossless round trips for registered/opaque attributes.

Newly generated codes have **no version byte**. Previously issued `F1`-marker UPIC codes can be imported; re-encoding converts them into positional-hex form (including moving hearts into the original pattern slot). Their old textual code can still be read. Existing original 12-character Maker IDs remain unchanged.

## Rendering contract

- `assets/svg/cuts/`, `assets/svg/patterns/`, and `assets/svg/registry.json` contain the MIT-licensed authoritative SVG source code and hexadecimal IDs. `assets/artwork.json` is compiled from those sources and preserves the original SVG strings. The viewBox remains `0 0 69.21 69.21` for figure-overlay compatibility. See `SVG_SPEC.md`.
- `assets/edge_profiles.json` is a **registered geometry table** automatically derived from the original front/back SVG silhouettes: for **each cut** there are distinct `waist`, `left_leg`, `right_leg` polylines and their outward normal orientations.
- Trims render **on the selected contour**, not on the whole silhouette through rectangular clipping. Scallops are actual joined quadratic arcs projected outward from the edge. Other edge variants are picot dots, waves, ruffles, zigzag or fine fringe, also edge-aligned.
- Material rendering adds lace/crochet motifs, beading, ribbon highlights, stitching, etc. Decor details use cut-specific anchors. Fabric patterns remain separate from edge finishes.
- New cut SVG templates can render without contour metadata if there are no trims (and no optional details); to use placed trims, the cut must register corresponding side-specific contours. An unknown cut/pattern/material value is a valid reference **until** an application tries to render it; then registration is required.
- The generated visual effects are stylized, deterministic SVG artwork, not claims about exact garment construction or textile physics. Replacing the visual assets with detailed production artwork **must not change** numeric IDs or their semantics.

## Examples and integration

`examples.json` contains four encoded examples with independent front/back SVG previews: `lace-scallop`, `picot-contrast`, `crochet-wave`, `eyelash-lace`. The responsive `index.php` allows editing waist/leg trims, right-leg independently, and an extra waist layer. The API accepts JSON designs; `UPIC::encode`, `decode`, `render` are the primary PHP operations.

Panty Lane Panty Drawers should save a UPIC **design code** and a separate authenticated drawer item/ownership record. Code access must never be interpreted as permission to create or transfer property. Maker → Drawer integration can reuse the same code without duplicating PNG/SVG assets.

## Open-source SVG development and reference governance

The original Panty Maker SVG creator has authorized releasing all vector source code under MIT. Every new cut or pattern needs a reviewed hexadecimal reference in `assets/svg/registry.json`, its own validated SVG file, both-side renders, and any contour/anchor updates. Fabric, trim, edge and detail references are registered as well; those families currently render procedurally and require a renderer change to support new SVG-defined implementations.

Visual definitions can be improved without reassigning their identity. Forks may distribute modifications under MIT; unmerged fork-only IDs are not globally interchangeable because the current code has no vendor namespace. See `SVG_SPEC.md` and `GOVERNANCE.md`.

## Registered common print references (UPIC 1.0 RC3)

Pattern values occupy the original two-hex-digit field in the nine-byte core; the reference is not an appended attribute. These values are assigned by this registry and must not be reused.

| Hex | Print | Source |
| --- | --- | --- |
| `09` | Small hearts | Original Maker extension |
| `0A` | Large hearts | Original Maker extension |
| `0B` | Small floral | Maker v1.17 SVG |
| `0C` | Large floral | Maker v1.17 SVG |
| `0D` | Leopard | Maker v1.17 SVG |
| `0E` | Lace look | Maker v1.17 SVG |
| `0F` | Stars | Maker v1.17 SVG |
| `10` | Camouflage | Maker v1.17 SVG |
| `11` | Plaid | Maker v1.17 SVG |

The lace-look **print** (`0E`) is distinct from actual lace **fabric** (`fabrics:06`) and lace **trim material** (`trim_materials:02`), which may be combined independently. Replacing provisional RC2 floral paths with Maker v1.17 corrected the visual definitions, without reinterpreting the print references.
