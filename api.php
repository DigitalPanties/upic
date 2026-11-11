<?php
declare(strict_types=1);
require_once __DIR__.'/src/UPIC.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        $code=(string)($_GET['code']??'');$data=UPIC::decode($code);
        echo json_encode(['ok'=>true,'design'=>$data],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }elseif($_SERVER['REQUEST_METHOD']==='POST') {
        if ((int)($_SERVER['CONTENT_LENGTH']??0)>16384)throw new InvalidArgumentException('Request too large');
        $d=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
        if(!is_array($d))throw new InvalidArgumentException('Expected design object');
        $code=UPIC::encode($d);
        echo json_encode(['ok'=>true,'code'=>$code,'design'=>UPIC::decode($code)],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }else{header('Allow: GET, POST');http_response_code(405);echo json_encode(['ok'=>false,'error'=>'Method not allowed']);}
}catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_SLASHES);}
