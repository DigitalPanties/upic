# Project governance and reference compatibility

UPIC is an **open specification and open-source reference implementation**. The original SVG creator is releasing the cut and pattern SVG source code under MIT as part of the project; community improvements may be reviewed and merged into the canonical repository.

## How contributions become official

1. Contributors fork the project or open a feature branch and propose a new reference or an implementation refinement through a pull request.
2. CI verifies SVG contract, registry/renderer agreement, and existing-code regression tests. The author supplies both-side visuals and demonstrates any reference/placement behaviour.
3. Maintainers decide whether the change preserves protocol meaning, is appropriately licensed, is safe for the renderer, and improves the project. **A passing CI run does not guarantee merge.**
4. After acceptance, the pull request is merged into the upstream registry. Existing stable reference meanings and the original nine-byte code layout are immutable.

## Forks and independent implementations

MIT permits copying, modifying, hosting, and redistributing the renderer and SVG source. Independent projects are encouraged to identify their own registry/renderer revisions and to disclose custom reference values that are not recognized upstream. Since UPIC IDs do not currently include a namespace for fork-specific IDs, independent additions may conflict. A fork-specific reference is not automatically an official UPIC allocation or universally renderable. If interoperability is desired, submit the reference for upstream allocation and review before relying on it externally.

## Changing visuals vs. changing identities

An improvement to a cut's curves or a cleaner lace motif can keep its current reference if it represents the same design concept. Assign a new reference for meaningfully different cuts, patterns, fabrics, trims, or details. Historical outputs may be retained as regression fixtures while renderer improvements are documented and reviewed.

For breaking changes to encoding or registry rules, propose the specification change in an issue first. The numeric identity and positional semantics are more important than any single rendering technique.

## Licensing and brand

Every tracked SVG and geometry definition is code under the repository's [MIT License](LICENSE). Pull requests certify contributor rights. The MIT grant covers source code, not trademark rights to Panty Lane or other brands.
