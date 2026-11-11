# UPIC 1.0 — public specification baseline candidate

**Candidate release:** `v1.0.0-rc.3` (11 November 2026). **Status:** proposed public baseline; not yet an official GitHub release. This file records a release decision, not a claim that any remote repository was updated.

UPIC is an open source design-identification protocol with a reference PHP/SVG implementation. Panty Maker is its first consumer, not the authority for assigning protocol references.

## Scope of the baseline

The normative UPIC v1 format is defined in [`PROTOCOL.md`](PROTOCOL.md) and the shared SVG contract in [`SVG_SPEC.md`](SVG_SPEC.md). Its core obligations are:

1. **Historical Maker core is stable:** exactly nine bytes, hexadecimal field positions as documented, encoded with modified Base64. Any original design ID retains its original twelve-character representation and semantic meaning. Any extended ID preserves those first nine bytes and first twelve characters.
2. **Single current extension syntax:** optional appended TLV records, canonical ordering, checksum, registered hex references and defined values. No `UP1-` textual prefix and no leading binary version byte. Future features extend this syntax without reinterpreting existing records.
3. **Reference identity is stable per family:** `01` means one thing *within* cuts, another within fabrics and so on. IDs are never reused for different concepts. New SVGs/cuts/patterns/effects may be registered using unassigned numbers; published codes keep their meanings.
4. **Separation of concerns:** a UPIC string describes visual design only. Ownership, account access, maker/share URLs, inventory, size, commerce and profiles belong to consumer applications.
5. **SVGs are source code:** original and contributed SVG source, contours, anchors and procedural implementations are MIT-licensed code, checked and reviewed through the canonical registry.
6. **Unknown references:** compatible decoders may preserve and expose unknown *optional* references without guessing their meaning; renderers must never silently substitute a different cut or motif. Unknown critical tags remain errors.

## Compatibility commitments

| Format | Status in v1.0 candidate | Requirement |
| --- | --- | --- |
| Original nine-byte Maker code | Normative and permanent | Decode, round-trip and render when its SVG reference is registered |
| Current canonical positional TLV extensions | Normative candidate | Byte-stable decode/encode and consistent reference meanings |
| Internal experimental `F1` extensions | Migration-only | Can be imported by this PHP library; do not generate or require new implementations to support them |
| Internal `UP1-` prefixed drafts | Not part of UPIC 1.0 | No interoperability promise |
| Panty Maker layout, backgrounds, controls | Outside protocol | Consumer implementation can evolve independently |

**Before public finalization:** test the current Panty Maker deployment against canonical `encode/decode/render` and confirm no internally issued extended IDs were overlooked. Because there are no third-party deployments known to the project, experimental formats need not impose permanent compatibility costs; still migrate existing in-house codes instead of silently losing them.

## Versioning and releases

The protocol release is identified by its **Git tag and specification revision**, not by inserting text or redundant version bytes into design codes. `v1.0.0` is the first proposed stable public release, after this candidate passes integration checks. Additive IDs, optional tags, SVG fixes and backwards-compatible refinements may be released in `1.x`; changes that reinterpret an issued code require a major version with an explicit migration. Renderer visuals can improve within the same semantic identity, but changes should be documented and visually reviewed.

Independent forks may have private ID allocations. They must clearly identify their registry revisions; upstream does not silently treat fork-specific conflicting IDs as universally defined.

## Release validation

From the repo root:

```sh
python3 tools/svg_registry.py check
php tests/registry_contract.php
php tests/run.php
php tests/maker_interop.php
php tests/baseline_wire.php
node examples/style-brief/tests/test.js
```

Review `INTEGRATION.md` for the independent Maker-to-UPIC boundary and `GOVERNANCE.md` for reference allocations.

**Registry baseline SHA-256** (tracking only, not the UPIC version): `cd79b5d94216983da312403aed2dc378ccfd2581b11ebed8ea99c3747e85c230`.
This hash will change when new references are legitimately added; identity stability is guaranteed by governance and tests, not by freezing the registry file forever.

## Maker v1.17 pattern compatibility verification (RC3)

Pattern references `0B` through `11` are registered against the exact SVG pattern definitions shipped in Panty Maker v1.17. The original `0B`/`0C` provisional RC2 floral artwork was replaced with v1.17's implementation; the references remain unchanged. The repository includes independent pinned source hashes at `tests/fixtures/maker_v1_17_patterns.json` and tests across all 10 cuts and both sides.

The 9-byte wire format, original 12-character codes, registered legacy references, CRC32/TLV extensions, and SVG source governance are unchanged. v1.0.0 remains a *candidate* until the public repository is created and the integration gate passes.
