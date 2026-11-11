<?php
declare(strict_types=1);
require_once __DIR__.'/src/UPIC.php';
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; sandbox');
try{
    $code=(string)($_GET['code']??($_GET['id']??''));
    $side=(string)($_GET['side']??'front');
    $svg=UPIC::render($code,$side);
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public,max-age=86400,immutable');
    echo $svg;
}catch(Throwable $e){http_response_code(400);header('Content-Type: text/plain; charset=utf-8');echo 'Invalid UPIC design';}
