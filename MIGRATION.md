# Migration to the UPIC 1.0 baseline candidate

## Existing original Panty Maker IDs

Do nothing. The nine-byte layout and twelve-character ID are the normative core. The updated renderer should decode the original IDs without any conversion or rewriting.

## Current extended UPIC IDs produced by Panty Maker

Preserve the exact original nine-byte core including coarse `#RGB` positions. Treat exact `#RRGGBB` overrides as appended optional data, not as a reason to rewrite the core. Run `php tests/maker_interop.php` before integration.

## Experimental F1-marker drafts

The reference PHP decoder currently accepts an earlier experimental `F1` variant for internal import, but the canonical encoder emits *only* the current appended-TLV format. If you have such IDs in your own systems, convert and verify them before treating `v1.0.0` as the stable public release. External implementers are not required to support experimental formats. Do not delete in-house codes without a verified migration.

## Experimental UP1-prefixed drafts

Not a canonical UPIC format. Convert from retained original design data, if any, rather than adding the prefix to the official protocol.

## Releasing consumer applications

Keep Maker and Panty Lane application releases separate from the UPIC specification. Test their codecs against the tagged upstream release and do not copy Maker-specific UI or figure-alignment logic into the source protocol library.
