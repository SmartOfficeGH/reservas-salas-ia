<?php
declare(strict_types=1);
ini_set('display_errors','0');
error_reporting(E_ALL);
$private=dirname(__DIR__,2).'/reservas-salas-private';
if(!is_dir($private))$private=dirname(__DIR__).'/private';
require $private.'/app/bootstrap.php';
try {
    $config=require $private.'/config.php';
    initialize($config,true);
    if(!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)) {
        header('Allow: GET, HEAD');http_response_code(405);exit('Esta vista solo permite consultar la ocupación.');
    }
    $day=is_string($_GET['day']??null)?$_GET['day']:'';
    $view=is_string($_GET['view']??null)?$_GET['view']:'week';
    $rawRoom=is_string($_GET['room']??null)?$_GET['room']:'';
    $cal=Calendar::state($day,$view,(int)$rawRoom);
    if($cal['view']==='day'&&($rawRoom===''||$rawRoom==='all'))$cal['room']='all';
    $cal['public']=true;$day=$cal['day'];$isPublic=true;
    $service=new Service(Service::connect($config),static function():void{throw new RuntimeException('Read only');});
    $rooms=$service->publicRooms();$agenda=$service->publicPeriod($cal['start'],$cal['end']);
    require $private.'/app/public-view.php';
} catch(Throwable $e) {
    error_log('Ocupación pública: error interno de tipo '.get_class($e));
    http_response_code(503);header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se puede consultar la ocupación en este momento. Inténtalo más tarde.';
}
