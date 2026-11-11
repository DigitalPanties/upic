<?php
declare(strict_types=1);
require_once __DIR__.'/../src/UPIC.php';
$dir=dirname(__DIR__);
$presets=[
  'lace-scallop'=>['style'=>5,'pattern'=>0,'sheerness'=>1,'colors'=>['front'=>'#d8487b','back'=>'#d8487b','trim'=>'#f4d8e7','pattern'=>'#ffffff'], 'fabric'=>'satin',
    'trims'=>[['zone'=>'waist','material'=>'lace','edge'=>'scalloped','width'=>4,'color'=>'#f7ddf0'],['zone'=>'left_leg','material'=>'lace','edge'=>'scalloped','width'=>3,'color'=>'#f7ddf0'],['zone'=>'right_leg','material'=>'lace','edge'=>'scalloped','width'=>3,'color'=>'#f7ddf0']],
    'details'=>[['type'=>'bow','at'=>'front_center','color'=>'#fff0fc','size'=>2]]],
  'picot-contrast'=>['style'=>3,'pattern'=>9,'sheerness'=>1,'colors'=>['front'=>'#22253a','back'=>'#22253a','trim'=>'#f2f2f2','pattern'=>'#de669b'], 'fabric'=>'mesh',
    'trims'=>[['zone'=>'all_edges','material'=>'elastic','edge'=>'picot','width'=>3,'color'=>'#e9b8da'],['zone'=>'waist','layer'=>1,'material'=>'ribbon','edge'=>'smooth','width'=>2,'color'=>'#ffffff']],
    'details'=>[['type'=>'pearl','at'=>'front_center','color'=>'#c08cb3','size'=>2]]],
  'crochet-wave'=>['style'=>7,'pattern'=>0,'sheerness'=>0,'colors'=>['front'=>'#77c2b2','back'=>'#77c2b2','trim'=>'#2d7e7a','pattern'=>'#edf7f6'],'fabric'=>'cotton',
    'trims'=>[['zone'=>'all_edges','material'=>'crochet','edge'=>'wavy','width'=>3,'color'=>'#f5e9ce'],['zone'=>'waist','layer'=>1,'material'=>'beaded','edge'=>'smooth','width'=>1,'color'=>'#bd846b']],
    'details'=>[['type'=>'flower','at'=>'left_hip','color'=>'#f2dad6','size'=>2]]],
  'eyelash-lace'=>['style'=>2,'pattern'=>0,'sheerness'=>4,'colors'=>['front'=>'#48527f','back'=>'#48527f','trim'=>'#141f47','pattern'=>'#e6e4ee'],'fabric'=>'stretch_lace',
    'trims'=>[['zone'=>'waist','material'=>'lace','edge'=>'eyelash','width'=>4,'color'=>'#c6afda'],['zone'=>'left_leg','material'=>'satin_binding','edge'=>'smooth','width'=>2,'color'=>'#eee4ff'],['zone'=>'right_leg','material'=>'satin_binding','edge'=>'smooth','width'=>2,'color'=>'#eee4ff']],
    'details'=>[['type'=>'rosette','at'=>'back_center','color'=>'#f3d1d5','size'=>2]]],
];
$out=[];
foreach($presets as $name=>$design){
  $code=UPIC::encode($design);$entry=['code'=>$code,'design'=>UPIC::decode($code)];$out[$name]=$entry;
  foreach(['front','back'] as $side)file_put_contents("$dir/preview-$name-$side.svg",UPIC::render($code,$side));
}
file_put_contents("$dir/examples.json",json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
