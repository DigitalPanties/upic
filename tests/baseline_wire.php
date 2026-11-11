<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/UPIC.php';

function wireCheck(bool $ok, string $msg): void {
    if (!$ok) throw new RuntimeException('UPIC v1 wire baseline: ' . $msg);
}

// Original nine-byte code from original Panty Maker release.
$original='ANoErvYLfCkI';
$o=UPIC::decode($original);
wireCheck(UPIC::encode($o)===$original, 'original ID changed');
wireCheck($o['style']===8 && $o['pattern']===4, 'original cut or print reassigned');
wireCheck(strlen($original)===12, 'original length changed');

// A real issued Maker v1.9 extended example; exact overrides may differ from core RGB.
$maker='D0gJD8|||5sEAQECEAcBAgED|3yrIAYBAQP|||8wBAH|RGbiAFu7';
$m=UPIC::decode($maker);
wireCheck($m['legacyCode']==='D0gJD8|||5sE', 'original prefix changed');
wireCheck($m['pattern']===9 && $m['style']===4, 'issued reference meaning changed');
wireCheck($m['colors']['front']==='#ff4466', 'full colour override lost');
wireCheck(UPIC::encode($m)===$maker, 'extended canonical ID changed');
wireCheck(str_contains(UPIC::render($maker,'front'), '<svg'), 'registered cut no longer renders');

// Registered hearts occupy the original pattern field, not an extra extension tag.
$h=UPIC::encode(['style'=>5,'pattern'=>10,'sheerness'=>0, 'colors'=>[
    'front'=>'#abc','back'=>'#abc','trim'=>'#fff','pattern'=>'#123'
]]);
wireCheck(strlen($h)===12, 'heart reference no longer fits original format');
wireCheck(UPIC::decode($h)['pattern']===10, 'heart registry reference changed');

// New attributes append TLVs, not a new prefix, leaving the original first 12 characters unchanged.
$extended=UPIC::encode(['style'=>5,'pattern'=>10,'sheerness'=>0,'fabric'=>'lace','colors'=>[
    'front'=>'#abc','back'=>'#abc','trim'=>'#fff','pattern'=>'#123'
]]);
wireCheck(str_starts_with($extended,$h),'TLV changed original bytes');
wireCheck(UPIC::decode($extended)['fabric']==='lace', 'fabric TLV ref lost');
wireCheck(UPIC::encode(UPIC::decode($extended))===$extended, 'TLV round trip changed');

echo "UPIC v1 wire baseline: PASS\n";
