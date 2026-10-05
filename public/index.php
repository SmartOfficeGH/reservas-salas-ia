<?php
declare(strict_types=1);
ini_set('display_errors','0');
error_reporting(E_ALL);
// En hosting: public se copia al subdominio y private a /home/delanada/reservas-salas-private.
$private = dirname(__DIR__,2).'/reservas-salas-private';
if (!is_dir($private)) $private = dirname(__DIR__).'/private';
if (!is_file($private.'/config.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Aplicación pendiente de configuración. Consulta las instrucciones de instalación.');
}
require $private.'/app/bootstrap.php';
$error = ''; $user = false;
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'agenda';
$pages = ['agenda','mine','login','register','forgot','resend','verify','reset'];
if (!in_array($page,$pages,true)) $page='agenda';
try {
    $config = require $private.'/config.php';
    initialize($config);
    $db = Service::connect($config);
    $service = new Service($db, smtpMailer($config));
    if (in_array($page,['verify','reset'],true) && isset($_GET['token'])) {
        $raw = is_string($_GET['token']) ? $_GET['token'] : '';
        $_SESSION['pending_link'] = ['kind'=>$page,'token'=>$raw,'saved'=>time()];
        redirect('?page='.$page); // Quitar token del URL antes de mostrar contenido.
    }
    if (isset($_SESSION['uid'])) {
        $user = $service->user((int)$_SESSION['uid']);
        if (!$user || !$user['verified_at'] || (int)$user['auth_version'] !== (int)($_SESSION['version'] ?? 0)) { forgetSession(); $user=false; }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();
        $action = input('action');
        if (in_array($action,['register','login','forgot','resend','verify','reset'],true)) {
            $service->throttle($action.'|'.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            if (in_array($action,['register','forgot','resend'],true)) $service->throttle('email|'.strtolower(trim(input('email'))),5);
        }
        switch ($action) {
            case 'register':
                $service->register(input('email'),input('password'));
                $_SESSION['flash']='Si la dirección puede registrarse, recibirás un enlace de verificación. Revisa también el correo no deseado. Si ya tienes cuenta, accede o solicita otro enlace.';
                redirect('?page=login');
            case 'login':
                $u = $service->login(input('email'),input('password'));
                forgetSession();
                $_SESSION['uid']=(int)$u['id']; $_SESSION['version']=(int)$u['auth_version'];
                $_SESSION['started']=time(); $_SESSION['last_seen']=time();
                redirect('?page=agenda');
            case 'logout':
                forgetSession(); session_destroy();
                setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>(bool)$config['secure_cookies'],'httponly'=>true,'samesite'=>'Lax']);
                redirect('?page=login');
            case 'forgot': case 'resend':
                $service->requestToken(input('email'),$action==='forgot'?'reset':'verify');
                $_SESSION['flash']='Si la cuenta reúne las condiciones, recibirás un enlace por correo. Revisa también el correo no deseado.';
                redirect('?page=login');
            case 'verify': case 'reset':
                $link = $_SESSION['pending_link'] ?? [];
                if (($link['kind'] ?? '') !== $action || time()-($link['saved'] ?? 0)>1800) throw new UserError('Abre de nuevo el enlace del correo o solicita uno nuevo.');
                $service->consumeToken($link['token'],$action,$action==='reset'?input('password'):null);
                forgetSession();
                $_SESSION['flash']=$action==='verify'?'Correo verificado. Ya puedes iniciar sesión.':'Contraseña actualizada. Inicia sesión con la nueva contraseña.';
                redirect('?page=login');
            case 'book': case 'cancel':
                if (!$user) { http_response_code(403); throw new UserError('Inicia sesión con tu correo verificado.'); }
                if ($action==='book') {
                    $service->book((int)$user['id'],['room_id'=>(int)input('room_id'),'concept'=>input('concept'),'day'=>input('day'),'start'=>input('start'),'end'=>input('end')]);
                    $_SESSION['flash']='Reserva confirmada. Puedes consultarla en Mis reservas.';
                    $returnView=in_array(input('calendar_view'),['day','week','month'],true)?input('calendar_view'):'week';
                    redirect('?page=agenda&day='.urlencode(input('day')).'&view='.$returnView.'&room='.(int)input('room_id'));
                }
                $service->cancel((int)$user['id'],(int)input('reservation_id'));
                $_SESSION['flash']='Reserva cancelada. El horario vuelve a estar disponible.';
                redirect('?page=mine');
            default: throw new UserError('Acción no válida.');
        }
    }
} catch (UserError $e) { $error=$e->getMessage(); }
catch (Throwable $e) {
    // No registrar cuerpos POST, URLs con token, credenciales ni mensajes SMTP.
    error_log('Reservas: error interno de tipo '.get_class($e));
    $error='No se ha podido completar la operación. Inténtalo más tarde. Si estabas registrándote, puedes solicitar un nuevo enlace de verificación.';
    http_response_code(503);
}
if (!isset($service)) {
    http_response_code(503); header('Content-Type: text/plain; charset=UTF-8'); exit($error ?: 'Servicio no disponible.');
}
if (!$user && in_array($page,['agenda','mine'],true)) $page='login';
if ($user && in_array($page,['login','register','forgot','resend'],true)) $page='agenda';
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$day = is_string($_GET['day'] ?? null) ? $_GET['day'] : (new DateTimeImmutable('today',new DateTimeZone('Europe/Madrid')))->format('Y-m-d');
$date = DateTimeImmutable::createFromFormat('!Y-m-d',$day);
if (!$date || $date->format('Y-m-d') !== $day) $day=date('Y-m-d');
$rooms=[]; $agenda=[]; $mine=[];
$cal=Calendar::state($day,is_string($_GET['view']??null)?$_GET['view']:'week',(int)(is_scalar($_GET['room']??null)?$_GET['room']:1));
try {
    if ($user) { $rooms=$service->rooms(); if ($page==='agenda') $agenda=$service->period($cal['start'],$cal['end']); if ($page==='mine') $mine=$service->mine((int)$user['id']); }
} catch (Throwable $e) { http_response_code(503); $error='No se pueden consultar las reservas en este momento.'; }
require $private.'/app/view.php';
