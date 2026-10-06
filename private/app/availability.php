<?php
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET');echo json_encode(['available'=>false,'message'=>'Solo se permite consultar.']);exit;}
if(!$user){http_response_code(403);echo json_encode(['available'=>false,'message'=>'Inicia sesión con tu correo verificado.']);exit;}
try {
    $param=static fn($key)=>is_string($_GET[$key]??null)?$_GET[$key]:'';
    $result=$service->availability((int)$param('room'),$param('day'),$param('start'),$param('end'));
    echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
} catch(UserError $e){http_response_code(422);echo json_encode(['available'=>false,'proposed_end'=>'','message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
exit;
