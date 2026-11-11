<?php
declare(strict_types=1);
require_once __DIR__.'/../src/UPIC.php';

$registry = json_decode(file_get_contents(__DIR__.'/../assets/svg/registry.json'), true, 512, JSON_THROW_ON_ERROR);
$families = [
    'fabrics' => UPIC::FABRICS,
    'trim_materials' => UPIC::MATERIALS,
    'edge_finishes' => UPIC::EDGES,
    'details' => UPIC::DETAILS,
];
foreach ($families as $family => $map) {
    $expected = [];
    foreach ($map as $name => $id) {
        $expected[strtoupper(str_pad(dechex($id), 2, '0', STR_PAD_LEFT))] = $name;
    }
    $actual = [];
    foreach ($registry[$family] as $hex => $item) {
        $actual[$hex] = $item['name'];
    }
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: $family registry values diverge from UPIC.php\n");
        exit(1);
    }
}
$art = json_decode(file_get_contents(__DIR__.'/../assets/artwork.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($art['templates']) !== 1 + count($registry['cuts'])) {
    fwrite(STDERR, "FAIL: cut source count mismatch\n");
    exit(1);
}
foreach ($registry['cuts'] as $hex => $item) {
    $id = (string) hexdec((string)$hex);
    if (($art['names'][$id] ?? null) !== $item['name'] || !isset($art['templates'][$id])) {
        fwrite(STDERR, "FAIL: mismatched cut $hex\n");
        exit(1);
    }
}
foreach ($registry['patterns'] as $hex => $item) {
    $id = (string) hexdec((string)$hex);
    if ($item['renderer'] === 'svg' && !isset($art['patterns'][$id])) {
        fwrite(STDERR, "FAIL: missing compiled pattern $hex\n");
        exit(1);
    }
    if ($item['renderer'] === 'builtin' && !in_array((int) $id, [9, 10], true)) {
        fwrite(STDERR, "FAIL: unknown builtin pattern $hex\n");
        exit(1);
    }
}
// All original cut and print IDs still round-trip and render both sides after source split.
foreach (range(1, 10) as $cut) {
    foreach (array_keys($registry['patterns']) as $patternHex) {
        $pattern = hexdec((string)$patternHex);
        $code = UPIC::encode(['style' => $cut, 'pattern' => $pattern]);
        if (UPIC::decode($code)['style'] !== $cut || UPIC::decode($code)['pattern'] !== $pattern) {
            fwrite(STDERR, "FAIL: reference round-trip ($cut, $pattern)\n");
            exit(1);
        }
        foreach (['front', 'back'] as $side) {
            if (!str_contains(UPIC::render($code, $side), '<svg')) {
                fwrite(STDERR, "FAIL: renderer ($cut, $pattern, $side)\n");
                exit(1);
            }
        }
    }
}
echo "Registry/PHP agreement: OK (cuts 1-10, all registered patterns, both sides)\n";
