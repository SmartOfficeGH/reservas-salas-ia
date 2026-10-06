<?php
declare(strict_types=1);
require_once __DIR__.'/Service.php';
require_once __DIR__.'/Occupancy.php';
require_once __DIR__.'/Calendar.php';

function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function assetUrl(string $name): string
{
    if (!in_array($name,['estilos.css','corporativo.css','app.js','compartir.js'],true)) throw new RuntimeException('Unknown asset');
    // La entrada PHP está en la carpeta pública tanto en local como en Webempresa.
    $path=dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.$name;
    $hash=is_file($path)?hash_file('sha256',$path):false;
    if ($hash===false) throw new RuntimeException('Missing public asset');
    return $name.'?v='.$hash;
}
function redirect(string $path): never { header('Location: '.$path, true, 303); exit; }
function csrfField(): string { return '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function checkCsrf(): void
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403);
        throw new UserError('El formulario ha caducado. Recarga la página y vuelve a intentarlo.');
    }
}
function input(string $key): string { return is_string($_POST[$key] ?? null) ? $_POST[$key] : ''; }
function forgetSession(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function initialize(array $config,bool $publicReadOnly=false): void
{
    date_default_timezone_set('Europe/Madrid');
    $local = ($config['environment'] ?? '') === 'local';
    $url = parse_url($config['app_url']);
    if ((!$local && (($url['scheme'] ?? '') !== 'https' || !$config['secure_cookies'])) || ($local && !in_array($url['host'] ?? '', ['127.0.0.1','localhost'], true))) throw new RuntimeException('Invalid configuration');
    header('Content-Type: text/html; charset=UTF-8');
    $GLOBALS['style_nonce']=base64_encode(random_bytes(24));
    header("Content-Security-Policy: default-src 'none'; style-src 'self' 'nonce-".$GLOBALS['style_nonce']."'; script-src 'self'; img-src 'self'; font-src 'self'; connect-src ".($publicReadOnly?"'none'":"'self'")."; form-action 'self'; base-uri 'none'; frame-ancestors ".($publicReadOnly?'*':"'none'"));
    if (!$publicReadOnly) header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!$local) {
        header('Strict-Transport-Security: max-age=31536000');
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            // No confiar en Host ni cabeceras de proxy suministradas por el cliente.
            redirect(rtrim($config['app_url'],'/').'/');
        }
    }
    if ($publicReadOnly) return; // Consulta pública sin leer ni crear sesiones.
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    ini_set('session.use_trans_sid','0');
    $sessions = dirname(__DIR__).'/storage/sessions';
    if (!is_dir($sessions) && !mkdir($sessions,0700,true) && !is_dir($sessions)) throw new RuntimeException('Session storage');
    session_save_path($sessions);
    ini_set('session.gc_maxlifetime','28800');
    session_name('reservas_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>(bool)$config['secure_cookies'],'httponly'=>true,'samesite'=>'Lax']);
    if (!session_start()) throw new RuntimeException('Cannot start session');
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    if (isset($_SESSION['uid']) && (time()-($_SESSION['last_seen'] ?? 0) > 1800 || time()-($_SESSION['started'] ?? 0) > 28800)) forgetSession();
    if (isset($_SESSION['uid'])) $_SESSION['last_seen'] = time();
}
function reservationMailContent(array $config,string $kind,string $token):array
{
    $name='Aplicación Reserva Salas';
    $url=rtrim($config['app_url'],'/').'/?page='.$kind.'&token='.$token;
    return ['from_name'=>$name,'subject'=>($kind==='verify'?'Verifica tu correo':'Recupera tu contraseña').' · '.$name,
        'body'=>$name."\n\n".($kind==='verify'?"Confirma tu correo para poder acceder y reservar. El enlace caduca en 24 horas.":"Establece una contraseña nueva. El enlace caduca en 30 minutos.")."\n\n".$url."\n\nEl enlace es de un solo uso. Si no solicitaste este correo, ignóralo."];
}
function smtpMailer(array $config): Closure
{
    if (($config['environment'] ?? '') === 'local') {
        // Transporte de pruebas obligatorio en local: nunca contactar con SMTP.
        return static function(string $email, string $kind, string $token) use ($config): void {
            $dir = dirname(__DIR__).'/storage/test-mail';
            if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Test mail storage');
            $path = $dir.'/'.bin2hex(random_bytes(16)).'.json';
            if (file_put_contents($path,json_encode(array_merge(compact('email','kind','token'),reservationMailContent($config,$kind,$token)),JSON_THROW_ON_ERROR),LOCK_EX) === false) throw new RuntimeException('Test mail write');
            chmod($path,0600);
        };
    }
    return static function(string $email, string $kind, string $token) use ($config): void {
        require_once dirname(__DIR__).'/vendor/autoload.php';
        $s = $config['smtp'];
        if (empty($s['password']) || empty($s['host']) || !is_int($s['port']) || $s['port'] < 1 || $s['port'] > 65535 || $s['encryption'] !== 'tls') throw new RuntimeException('SMTP not configured');
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $s['host']; $mail->Port = $s['port'];
        $mail->SMTPAuth = true; $mail->Username = $s['username']; $mail->Password = $s['password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPDebug = 0; $mail->Timeout = 15;
        $mail->CharSet = 'UTF-8';
        $content=reservationMailContent($config,$kind,$token);
        $mail->setFrom($s['from'], $content['from_name']); $mail->addAddress($email);
        $mail->Subject = $content['subject'];
        $mail->Body = $content['body'];
        $mail->send();
    };
}
