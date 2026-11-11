# Floral registry release candidate — validation

$ python3 tools/svg_registry.py check
SVG registry check: OK (cuts=10, patterns=13, fabrics=12, trim_materials=10, edge_finishes=7, details=6; 21 SVG sources)

$ php tests/registry_contract.php
Registry/PHP agreement: OK (original cuts 1-10, patterns 0-10, both sides)

$ php tests/run.php
Passed 772; failed 0

$ php tests/maker_interop.php
Maker interoperability: PASS (legacy core, exact colour, trim/detail, renderer)

$ php tests/baseline_wire.php
UPIC v1 wire baseline: PASS

$ php tests/floral_patterns.php
Floral references: OK (2 prints × 10 cuts × 2 sides, legacy preservation)

$ node examples/style-brief/tests/test.js
PASS 11 checks: legacy, extended prefix, fabric/trim/detail/color, buyer brief, checksum, unknown optional, high-order, critical rejection, invalid zone, bad characters
sample=D0gJD8|||5sEAQECEAcBAgED|3yrIAYBAQP|||8wBAH|RGbiAFu7
legacy=D0gJD8|||5sE

$ node --check examples/style-brief/decoder.js


Maker v1.15 exact source not available for visual/ID comparison at build time.
