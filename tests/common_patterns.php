<?php
declare(strict_types=1);
require_once __DIR__.'/../src/UPIC.php';
function commonCheck(bool $ok,string $message): void {
    if(!$ok)throw new RuntimeException('Maker v1.17 patterns: '.$message);
}
$fixture=json_decode(file_get_contents(__DIR__.'/fixtures/maker_v1_17_patterns.json'),true,512,JSON_THROW_ON_ERROR);
$art=json_decode(file_get_contents(__DIR__.'/../assets/artwork.json'),true,512,JSON_THROW_ON_ERROR);
$expected=[11=>'Small floral',12=>'Large floral',13=>'Leopard',14=>'Lace look',15=>'Stars',16=>'Camouflage',17=>'Plaid'];
$checks=0;
foreach($expected as $num=>$name){
    $ref=strtoupper(str_pad(dechex($num),2,'0',STR_PAD_LEFT));
    $pattern=$art['patterns'][(string)$num]??null;
    commonCheck(is_string($pattern),'missing compiled pattern '.$ref);
    commonCheck(hash('sha256',$pattern)===$fixture['patterns'][$ref],'source SVG does not match Maker v1.17: '.$name);
    commonCheck(substr_count($pattern,'<pattern')===1 && substr_count($pattern,'</pattern>')===1,'unbalanced SVG pattern '.$name);
    $checks+=3;
    foreach(range(1,10) as $style){
        $spec=['style'=>$style,'pattern'=>$num,'colors'=>['front'=>'#e85','back'=>'#e85','pattern'=>'#529','trim'=>'#fff']];
        $code=UPIC::encode($spec);
        commonCheck(strlen($code)===12,'print '.$name.' should use original 9-byte code');
        $d=UPIC::decode($code);
        commonCheck($d['style']===$style && $d['pattern']===$num,'ref mismatch '.$name);
        commonCheck(UPIC::encode($d)===$code,'roundtrip failed '.$name);
        $checks+=3;
        foreach(['front','back'] as $side){
            $svg=UPIC::render($code,$side);
            commonCheck(substr_count($svg,'</pattern>')===1,'missing close pattern '.$name.'/'.$side);
            commonCheck(str_contains($svg,'#552299'),'pattern ink missing '.$name.'/'.$side);
            commonCheck(str_contains($svg,'#ee8855'),'base color missing '.$name.'/'.$side);
            commonCheck(!str_contains($svg,'rgb(66,66,66)'),'legacy placeholder not replaced '.$name.'/'.$side);
            $checks+=4;
        }
    }
    $base=UPIC::encode(['style'=>5,'pattern'=>$num,'colors'=>['front'=>'#e85','back'=>'#e85','trim'=>'#fff','pattern'=>'#529']]);
    $extended=UPIC::encode(['style'=>5,'pattern'=>$num,'colors'=>['front'=>'#e85','back'=>'#e85','trim'=>'#fff','pattern'=>'#529'],'fabric'=>'lace','trims'=>[[
        'zone'=>'waist','material'=>'lace','edge'=>'scalloped','width'=>3,'color'=>'#ffffff'
    ]]]);
    commonCheck(str_starts_with($extended,$base),'TLV extensions rewrote base bytes for '.$name);
    commonCheck(UPIC::encode(UPIC::decode($extended))===$extended,'extended roundtrip failed '.$name);
    $checks+=2;
}
$legacy='ANoErvYLfCkI';
commonCheck(UPIC::encode(UPIC::decode($legacy))===$legacy,'original Panty Maker bytes changed');$checks++;
echo 'Maker v1.17 pattern compatibility: PASS ('.$checks.' assertions; 7 prints × 10 cuts × 2 sides)'.PHP_EOL;
