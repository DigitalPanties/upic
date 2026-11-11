<?php
declare(strict_types=1);
require_once __DIR__.'/../src/UPIC.php';

function assertFloral(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('Floral UPIC: '.$message);
}
$legacy='ANoErvYLfCkI';
assertFloral(UPIC::encode(UPIC::decode($legacy))===$legacy,'original Maker bytes changed');
foreach ([11=>'Small floral',12=>'Large floral'] as $pattern=>$name) {
    foreach (range(1,10) as $cut) {
        $spec=['style'=>$cut,'pattern'=>$pattern,'sheerness'=>0,
          'colors'=>['front'=>'#e85','back'=>'#e85','trim'=>'#fff','pattern'=>'#529']];
        $code=UPIC::encode($spec);
        assertFloral(strlen($code)===12,"$name $cut requires no extension");
        $read=UPIC::decode($code);
        assertFloral($read['pattern']===$pattern && $read['style']===$cut,"$name $cut incorrect ref");
        assertFloral(UPIC::encode($read)===$code,"$name $cut round-trip failed");
        foreach (['front','back'] as $side) {
            $svg=UPIC::render($code,$side);
            assertFloral((str_contains($svg,'<pattern id="ptrn"') || str_contains($svg,"<pattern id='ptrn'")),"$name $cut $side pattern omitted");
            assertFloral(str_contains($svg,'#552299'),"$name $cut $side print ink missing");
            assertFloral(str_contains($svg,'#ee8855'),"$name $cut $side base fill missing");
            assertFloral(!str_contains($svg,'rgb(66,66,66)'),"$name $cut $side raw placeholder remains");
        }
    }
}
// A new floral print combines with existing optional attributes without changing its core.
foreach ([11,12] as $pattern) {
  $base=UPIC::encode(['style'=>5,'pattern'=>$pattern]);
  $withLace=UPIC::encode(['style'=>5,'pattern'=>$pattern,'fabric'=>'lace',
    'trims'=>[['zone'=>'waist','material'=>'lace','edge'=>'scalloped',
               'width'=>3,'color'=>'#ffffff']]]);
  assertFloral(substr($withLace,0,12)===substr($base,0,12),'floral/trim prefix changed');
  assertFloral(UPIC::encode(UPIC::decode($withLace))===$withLace, 'floral/trim round trip failed');
  assertFloral(str_contains(UPIC::render($withLace,'front'), 'ptrn'), 'floral/trim SVG missing');
}
print "Floral references: OK (2 prints × 10 cuts × 2 sides, legacy preservation)\n";
