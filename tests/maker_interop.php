<?php
declare(strict_types=1);
require_once __DIR__.'/../src/UPIC.php';

function ensure(bool $ok, string $msg): void { if (!$ok) throw new RuntimeException($msg); }

// Issued by the independently updated Panty Maker UPIC engine.
$code = 'D0gJD8|||5sEAQECEAcBAgED|3yrIAYBAQP|||8wBAH|RGbiAFu7';
$legacy = 'D0gJD8|||5sE';
$design = UPIC::decode($code);
ensure($design['legacyCode'] === $legacy, 'Original 12-character prefix changed');
ensure($design['style'] === 4 && $design['pattern'] === 9, 'Maker cut/pattern mismatch');
ensure($design['colors']['front'] === '#ff4466', 'Exact front RGB override lost');
ensure($design['coreColors']['front'] !== $design['colors']['front'], 'Original #RGB core is not retained');
ensure(count($design['trims']) === 1 && $design['trims'][0]['edge'] === 'scalloped', 'Edge reference mismatch');
ensure(count($design['details']) === 1, 'Decorative detail not decoded');
ensure(UPIC::encode($design) === $code, 'Maker code is not a lossless round trip');
ensure(str_contains(UPIC::render($code, 'front'), '<svg'), 'Cannot render Maker code');

// Changing exact colour on an existing legacy design must append an override,
// not rewrite its nine original bytes.
$fromLegacy = UPIC::decode($legacy);
$fromLegacy['colors']['front'] = '#ff4466';
$extended = UPIC::encode($fromLegacy);
ensure(str_starts_with($extended, $legacy), 'Adding override changed legacy code prefix');
ensure(UPIC::encode(UPIC::decode($extended)) === $extended, 'Override loses canonical round trip');

// The core low-reference fields cannot be silently inconsistent with the supplied prefix.
$wrong = UPIC::decode($legacy);
$wrong['style'] = 5;
try { UPIC::encode($wrong); throw new RuntimeException('Conflicting cut was accepted'); }
catch (InvalidArgumentException $ok) { }

echo "Maker interoperability: PASS (legacy core, exact colour, trim/detail, renderer)\n";
