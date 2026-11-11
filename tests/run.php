<?php
declare(strict_types=1);
require_once __DIR__.'/../src/UPIC.php';
$passed=0;$failed=0;
function check(bool $ok,string $message): void {global $passed,$failed;if($ok){$passed++;}else{$failed++;echo "FAIL: $message\n";}}
function rejects(callable $fn): bool{try{$fn();return false;}catch(InvalidArgumentException $e){return true;}}
$old='ANoErvYLfCkI';
$d=UPIC::decode($old);
check($d['format']==='legacy','legacy detection');
check($d['style']===8&&$d['pattern']===4&&$d['sheerness']===10,'decode known original design');
check(UPIC::encode($d)===$old,'legacy encoding exact roundtrip');
check(UPIC::encode($d,true)===$old,'no empty extension payload is emitted even when requested');
check(UPIC::render($old,'front')===file_get_contents(__DIR__.'/legacy_reference.svg'),'original historical SVG bytes preserved');
for($style=1;$style<=10;$style++){
    for($pat=0;$pat<=8;$pat++){
        $d=['style'=>$style,'pattern'=>$pat,'sheerness'=>random_int(0,14),
          'colors'=>['front'=>'#ff00ff','back'=>'#5599bb','trim'=>'#eeddaa','pattern'=>'#337799']];
        $c=UPIC::encode($d);
        check(strlen($c)===12,"legacy 12-char style $style pat $pat");
        check(UPIC::encode(UPIC::decode($c))===$c,"legacy roundtrip style $style pat $pat");
        check(str_contains(UPIC::render($c,'front'),'<svg'),"front SVG style $style pat $pat");
        check(str_contains(UPIC::render($c,'back'),'<svg'),"back SVG style $style pat $pat");
    }
}
$x=['style'=>5,'pattern'=>10,'sheerness'=>3,
'colors'=>['front'=>'#e83b6a','back'=>'#6cbbf0','trim'=>'#fff1dd','pattern'=>'#80173e'],
'fabric'=>'lace','trims'=>[
 ['zone'=>'waist','material'=>'lace','edge'=>'scalloped','width'=>4,'color'=>'#ffebdd'],
 ['zone'=>'left_leg','material'=>'elastic','edge'=>'picot','width'=>2,'color'=>'#eefaee'],
 ['zone'=>'right_leg','material'=>'elastic','edge'=>'picot','width'=>2,'color'=>'#eefaee']],
'details'=>[['type'=>'bow','at'=>'front_center','size'=>3,'color'=>'#80173e'],['type'=>'rosette','at'=>'back_center','size'=>2,'color'=>'#eeffff']]];
$c=UPIC::encode($x);$y=UPIC::decode($c);
check(!str_contains($c,'-') && strlen($c)>12,'extended code has no textual prefix');
check(substr($c,0,12)===$y['legacyCode'],'original code is exact leading 12 characters');
check(UPIC::decode(substr($c,0,12))['style']===$y['style'],'legacy engine reads the beginning');
$bytes=base64_decode(strtr($c,'*|','+/').str_repeat('=',(4-strlen($c)%4)%4),true);
check(ord($bytes[9])===0x01,'extension starts immediately with fabric reference, not a version byte');
check(substr($bytes,0,9)===base64_decode(strtr($y['legacyCode'],'*|','+/')),'legacy bytes untouched in extended design');
$base=['style'=>4,'pattern'=>1,'sheerness'=>3,'colors'=>['front'=>'#aabbcc','back'=>'#ddeeff','trim'=>'#ffffff','pattern'=>'#112233']];
$baseCode=UPIC::encode($base);
$withFabric=$base; $withFabric['fabric']='lace';
check(str_starts_with(UPIC::encode($withFabric),$baseCode),'appending a fabric does not change original code prefix');
$withDetail=$base; $withDetail['details']=[['type'=>'bow','at'=>'right_hip','size'=>2,'color'=>'#bada55']];
check(str_starts_with(UPIC::encode($withDetail),$baseCode),'appending a placed detail does not change original code prefix');
check($y['pattern']===10&&$y['fabric']==='lace'&&count($y['trims'])===3&&count($y['details'])===2,'extended traits survive');
check($y['colors']['front']==='#e83b6a','full RGB precision survives');
check(UPIC::encode($y)===$c,'canonical extended roundtrip');
check(str_contains(UPIC::render($c,'front'),'upic-contour-trims'),'scalloped trim SVG generated');
check(str_contains(UPIC::render($c,'back'),'upic-fabric'),'lace fabric SVG generated');
$x['unknown']=[['tag'=>0x52,'hex'=>'010203']];
$cc=UPIC::encode($x);check(UPIC::encode(UPIC::decode($cc))===$cc,'unknown optional TLV preserved');
check(rejects(fn()=>UPIC::decode('bad')), 'invalid legacy rejected');
check(rejects(fn()=>UPIC::decode(substr($c,0,-1).($c[-1]==='A'?'B':'A'))), 'corruption rejected');
check(UPIC::decode(UPIC::encode(['style'=>99]))['style']===99,'numeric cut 0x63 accepted as an encoded reference');
check(rejects(fn()=>UPIC::render(UPIC::encode(['style'=>99]))),'unregistered SVG cut cannot be visually fabricated');
check(rejects(fn()=>UPIC::encode(['style'=>5,'trims'=>[['zone'=>'waist','material'=>'lace','edge'=>'smooth','width'=>2,'color'=>'#fff'],['zone'=>'waist','material'=>'lace','edge'=>'smooth','width'=>2,'color'=>'#fff']]])),'duplicate zone rejected');
check(rejects(fn()=>UPIC::render($c,'top')),'invalid side rejected');

// Original fields are hex reference slots, not fixed 10-cut/9-pattern enums.
$representable=['style'=>5,'pattern'=>0,'sheerness'=>0,
'colors'=>['front'=>'#abc','back'=>'#def','trim'=>'#123','pattern'=>'#456']];
foreach([9,10,11,14,16,42,254,255] as $ref){
 $v=$representable;$v['pattern']=$ref;$pc=UPIC::encode($v);
 check(strlen($pc)===12,'pattern '.$ref.' uses existing 2-digit hex field');
 check(UPIC::decode($pc)['pattern']===$ref,'pattern '.$ref.' retains reference');
}
foreach([11,16,42,99,254,255] as $ref){
 $v=$representable;$v['style']=$ref;$pc=UPIC::encode($v);
 check(strlen($pc)===12,'cut '.$ref.' uses existing 2-digit hex field');
 check(UPIC::decode($pc)['style']===$ref,'cut '.$ref.' retains reference');
}
foreach([256,257,511,65535,65536,0x12345678,0xffffffff] as $ref){
 $v=$representable;$v['style']=$ref;$pc=UPIC::encode($v);
 check(strlen($pc)>12&&UPIC::decode($pc)['style']===$ref,'cut high-order hex extension '.$ref);
 $v=$representable;$v['pattern']=$ref;$pc=UPIC::encode($v);
 check(strlen($pc)>12&&UPIC::decode($pc)['pattern']===$ref,'pattern high-order hex extension '.$ref);
}
foreach([9,10] as $heart){
 $v=$representable;$v['pattern']=$heart;$pc=UPIC::encode($v);
 check(strlen($pc)===12,'heart print uses legacy length, not a separate TLV');
 check(str_contains(UPIC::render($pc,'front'),'id="ptrn"'),'heart print renders by reference');
}
$v=$representable;$v['style']=255;$v['pattern']=254;$v['sheerness']=15;
check(UPIC::decode(UPIC::encode($v))['sheerness']===15,'unused sheerness nibble available');
$v=$representable;$v['fabric']=200;
check(UPIC::decode(UPIC::encode($v))['fabric']===200,'future fabric ref preserved as numeric byte');
check(rejects(fn()=>UPIC::render(UPIC::encode($v))),'unregistered fabric renderer rejects rather than inventing texture');
$v=$representable;$v['trims']=[['zone'=>1,'material'=>200,'edge'=>12,'width'=>3,'color'=>'#abc']];
$vx=UPIC::decode(UPIC::encode($v));
check($vx['trims'][0]['material']===200&&$vx['trims'][0]['edge']===12,'future trim and edge ref bytes preserved');
check(rejects(fn()=>UPIC::render(UPIC::encode($v))),'unregistered trim renderer rejects');
$oldIssued=json_decode(file_get_contents(__DIR__.'/../previous-issued-example.json'),true);
$back=UPIC::decode($oldIssued['code']);
check($back['version']===1 && $back['pattern']===10,'import earlier F1 extended IDs');
$up=UPIC::encode($back);
check(UPIC::decode($up)['pattern']===10 && UPIC::decode($up)['version']===2,'old issued ID upgrades to positional codec');
check(substr($up,0,12)!==substr($oldIssued['code'],0,12),'heart pattern moves into the original pattern byte on upgrade');
check(UPIC::render($oldIssued['code'],'front')===UPIC::render($up,'front'),'old and updated extended IDs render same front design');


// Genuine cut-specific edge registration, per-side geometry and layer semantics.
$legacy=UPIC::decode($old);
$ink=$legacy['colors']['trim'];
foreach(range(1,10) as $cut){
    foreach(['front','back'] as $side){
        foreach(['waist','left_leg','right_leg'] as $zone){
            $test=['style'=>$cut,'trims'=>[['zone'=>$zone,'material'=>'lace','edge'=>'scalloped','width'=>3,'color'=>'#ebd9fa']]];
            $code=UPIC::encode($test);$round=UPIC::decode($code);
            $svg=UPIC::render($code,$side);
            check(str_contains($svg,'data-zone="'.$zone.'"'),'cut '.$cut.' '.$side.' only '.$zone.' present');
            foreach(array_diff(['waist','left_leg','right_leg'],[$zone]) as $other)
                check(!str_contains($svg,'data-zone="'.$other.'"'),'cut '.$cut.' '.$side.' excludes '.$other);
            check(str_contains($svg,' Q'),'cut '.$cut.' '.$side.' scallops are true quadratic arcs');
            check($round['trims'][0]['zone']===$zone && UPIC::encode($round)===$code,'cut '.$cut.' '.$side.' zone roundtrip');
        }
    }
}
$layered=['style'=>5,'pattern'=>9,'fabric'=>'stretch_lace',
    'colors'=>['front'=>'#aabbcc','back'=>'#aabbcc','trim'=>'#ffffff','pattern'=>'#ddeeff'],
    'trims'=>[
        ['zone'=>'all_edges','material'=>'lace','edge'=>'scalloped','width'=>4,'color'=>'#ffe0eb'],
        ['zone'=>'waist','material'=>'ribbon','edge'=>'picot','width'=>3,'color'=>'#ffddee'],
        ['zone'=>'waist','layer'=>1,'material'=>'beaded','edge'=>'smooth','width'=>1,'color'=>'#aaccdd'],
        ['zone'=>'right_leg','layer'=>2,'material'=>'crochet','edge'=>'wavy','width'=>2,'color'=>'#8899aa']
    ],
    'details'=>[['type'=>'flower','at'=>'back_center','size'=>2,'color'=>'#ffeedd']]
];
$lc=UPIC::encode($layered);$ld=UPIC::decode($lc);$ls=UPIC::render($lc,'front');
check(strlen($lc)>12 && substr($lc,0,12)===$ld['legacyCode'],'layered trims preserve original code prefix');
check(UPIC::encode($ld)===$lc,'layered trims canonical roundtrip');
check(count($ld['trims'])===4&&in_array(2,array_column($ld['trims'],'layer'),true),'layered trim records decoded');
check(str_contains($ls,'data-zone="waist" data-layer="1"'),'waist overlay uses second layer');
check(str_contains($ls,'data-zone="right_leg" data-layer="2"'),'leg can use distinct third layer');
check(!str_contains($ls,'data-zone="waist" data-layer="0" data-material="lace"'),'specific waist overrides all-edges default at the same layer');
check(str_contains($ls,'data-zone="left_leg" data-layer="0" data-material="lace"'),'all-edges default applied to left leg');
check(rejects(fn()=>UPIC::encode(['trims'=>[['zone'=>'waist','layer'=>1,'material'=>'lace','edge'=>'smooth','width'=>1,'color'=>'#fff'], ['zone'=>'waist','layer'=>1,'material'=>'lace','edge'=>'smooth','width'=>1,'color'=>'#fff']]])),'duplicate zone and layer rejected');
check(rejects(fn()=>UPIC::encode(['trims'=>[['zone'=>'waist','layer'=>4,'material'=>'lace','edge'=>'smooth','width'=>1,'color'=>'#fff']]])),'layer outside registered range rejected');
foreach(['crochet','mesh','satin_binding','beaded'] as $material){
    $p=['style'=>3,'trims'=>[['zone'=>'left_leg','material'=>$material,'edge'=>'zigzag','width'=>2,'color'=>'#bceef1']]];
    check(UPIC::decode(UPIC::encode($p))['trims'][0]['material']===$material,'extended material '.$material.' encoded');
    check(str_contains(UPIC::render($p,'front'),'data-material="'.$material.'"'),'extended material '.$material.' rendered');
}
foreach(['eyelash','zigzag','ruffled','wavy','scalloped','picot'] as $edge){
    $p=['style'=>3,'trims'=>[['zone'=>'waist','material'=>'lace','edge'=>$edge,'width'=>4,'color'=>'#bceef1']]];
    check(str_contains(UPIC::render($p,'front'),'data-edge="'.$edge.'"'),'extended edge '.$edge.' rendered');
}
foreach(['stretch_lace','chiffon','jersey','velvet'] as $fabric){
    $p=['style'=>3,'fabric'=>$fabric];
    check(UPIC::decode(UPIC::encode($p))['fabric']===$fabric,'new fabric '.$fabric.' encoded');
    check(str_contains(UPIC::render($p,'front'),'upic-fabric'),'new fabric '.$fabric.' rendered');
}
$future=['style'=>5,'trims'=>[['zone'=>201,'material'=>250,'edge'=>249,'width'=>2,'color'=>'#fff']]];
check(UPIC::decode(UPIC::encode($future))['trims'][0]['zone']===201,'future numeric placement references remain representable');
check(rejects(fn()=>UPIC::render($future)),'unregistered future placement cannot render');
 echo "Passed $passed; failed $failed\n";
exit($failed?1:0);
