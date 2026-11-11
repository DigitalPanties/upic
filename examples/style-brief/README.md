# UPIC Style Brief — standalone Panty Lane commerce-bridge prototype

**Status:** working standalone prototype, **not deployed** to Panty Lane and **not** a new version of the UPIC standard. Built for the existing November 10, 2026 UPIC positional protocol.

## What it does

Converts an existing Panty Maker/UPIC design ID into a readable style request, including cut, fabric, pattern, base colours, independent waist/leg trims, detail placements, and their registered rendering names. A potential buyer can add size, quantity, budget and notes, then copy a seller request, export read-only JSON, save a text file, or share a design URL.

**Why:** Someone who likes a digital design can communicate the same style to a creator or seller, with no requirement that Panty Lane process an order. This tests a lead-generation/application opportunity rather than committing to a new marketplace.

## Use locally

1. Unzip this folder.
2. Open `index.html` in a modern browser on desktop or mobile; no PHP, account, database or internet connection is required for decoding and generating briefs.
3. Click **Load sample design**, or paste a code from Panty Maker and click **Read design**.
4. Optional: fill in requested garment size, quantity, budget and notes. Choose **Copy request**, **Copy JSON**, **Save .txt**, or **Copy design link**.

The code has an optional **Open design** link to `https://pantylane.com/maker/show.php?id=...`, which requires the corresponding Maker version installed on the public website. The offline file does not attempt to fetch remote images, so it doesn't silently expose users' codes. On a deployed copy, **Copy design link** copies that deployed page's `?code=...` URL; when opened as a local file, it copies the Maker show link instead.

## Deploy without changing Maker

Upload `index.html` and `decoder.js` together to a **new** directory such as `/upic-brief/` on your Panty Lane server. Existing Maker files and UPIC libraries are untouched. A link on a Maker design page can send a code with the `?code=` query parameter. A safe PHP integration example is in `maker-integration-example.php.txt`.

**No live deployment was performed.** The URL `/upic-brief/` is a suggested location, not a URL currently known to exist.

## Compatibility and safety

- Original unextended 9-byte design IDs (12 modified-Base64 characters) remain unchanged.
- Extended IDs use TLV parsing and big-endian CRC32. Unknown optional tags are reported, not fabricated. Unknown critical tags are rejected. The earlier F1-marker draft code format is not migrated by this helper.
- Existing cut/pattern definitions, hearts, fabrics, trims, edge finishes, layer/zone placements, details and exact-colour overrides use approved November 10 UPIC registry values.
- High-order cut/pattern extension bytes (`41`, `42`) are decoded.
- The tool **does not** generate garment manufacturing patterns, verify goods exist, claim an item is owned, take payment, or identify a buyer.
- User-entered budget/size/notes are never added to the UPIC ID; they are only included in locally generated brief text.
- HTTP hosting is recommended to make share links. External/seller sites should not treat a UPIC code as authenticated evidence of any claim.

## Test results (2026-11-11)

- `node tests/test.js`: passed legacy ID, extension prefix preservation, fabric/trim/edge/detail/exact-colour decode, request output, CRC failure rejection, opaque optional tag preservation, high-order cut reference, critical-tag rejection, invalid trim zone rejection, and invalid character rejection.
- Independent PHP CRC32 check on the same sample payload: passed.
- `node --check decoder.js`: passed.
- Browser-level screenshot/interaction test: **not completed** in this runtime; please test the rendered interface in your own browser before using it publicly.

## Low-overhead experiment

Place the `?code` bridge link beside **Show & Tell** on Panty Maker, then ask a handful of existing buyers and sellers to try requesting/quoting a design. Measure *actual copied requests, messages sent, and seller responses*, not just visits. Avoid paid lead routing or commissions until real inquiries convert into real seller replies.

## Files

- `index.html`: responsive standalone application, with colours intentionally distinct from green-and-gold themes.
- `decoder.js`: read-only UPIC codec with checksum validation.
- `maker-integration-example.php.txt`: optional one-link integration for a Maker page that already has a design ID available.
- `tests/test.js`: Node compatibility and rejection tests.

The underlying UPIC protocol and original SVG artwork belong to their own project/repository. No original art assets were copied or relicensed into this prototype.

## UPIC RC3 print registry

The optional decoder recognizes Maker v1.17 patterns Small floral, Large floral, Leopard, Lace look, Stars, Camouflage and Plaid (`0B`–`11`). No change to its wire-format handling.
