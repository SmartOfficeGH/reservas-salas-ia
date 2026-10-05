<?php
declare(strict_types=1);
// Solo bases de pruebas; ejecuta tras importar database/001_initial.sql en una base VACÍA.
require __DIR__.'/../private/app/Service.php';
$config=require __DIR__.'/../private/config.php';
if ($config['environment']!=='local' || !str_ends_with($config['db']['name'],'_test')) exit("Requiere configuración local y base terminada en _test.\n");
$db=Service::connect($config); $outbox=[];
$service=new Service($db,static function($email,$kind,$raw) use (&$outbox){ $outbox[$email][$kind]=$raw; });
$total=0;
function ok(bool $pass,string $label): void { global $total; if (!$pass) throw new RuntimeException('FAIL '.$label); $total++; }
function deny(Closure $f,string $label): void { try {$f();} catch(UserError){ok(true,$label);return;}throw new RuntimeException('FAIL '.$label); }
$suffix=bin2hex(random_bytes(5)); $email='test.'.$suffix.'@palma.es'; $other='other.'.$suffix.'@palma.es'; $pw='Contraseña ficticia 2026';
$service->register($email,$pw); $u=$db->query("SELECT * FROM users WHERE email=".$db->quote($email))->fetch();
ok($u['verified_at']===null,'initial unverified'); ok($u['password_hash']!==$pw,'hashed password');
deny(fn()=>$service->login($email,$pw),'unverified login');
$day=(new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Madrid')));while((int)$day->format('N')>5)$day=$day->modify('+1 day');
$r=['room_id'=>1,'concept'=>'Prueba '.$suffix,'day'=>$day->format('Y-m-d'),'start'=>'09:00','end'=>'10:00'];
deny(fn()=>$service->book((int)$u['id'],$r),'unverified booking');
$raw=$outbox[$email]['verify'];$stored=$db->query('SELECT token_hash FROM email_tokens WHERE user_id='.(int)$u['id'])->fetchColumn();ok($stored===hash('sha256',$raw),'hashed token');
$db->exec('UPDATE email_tokens SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE user_id='.(int)$u['id']);
deny(fn()=>$service->consumeToken($raw,'verify'),'verification expiry');
$service->requestToken($email,'verify');$firstVerify=$outbox[$email]['verify'];
$service->requestToken($email,'verify');$raw=$outbox[$email]['verify'];
deny(fn()=>$service->consumeToken($firstVerify,'verify'),'verification resend invalidates previous');
deny(fn()=>$service->consumeToken($raw,'reset','Otra contraseña ficticia'),'verification cannot reset password');
deny(fn()=>$service->consumeToken(str_repeat('a',64),'verify'),'unknown token');
$service->consumeToken($raw,'verify'); deny(fn()=>$service->consumeToken($raw,'verify'),'one use');
$logged=$service->login($email,$pw);ok($logged['verified_at']!==null,'verified login');deny(fn()=>$service->login($email,'Wrong'),'wrong password');
$service->register($email,'Otra contraseña ficticia');
ok($service->login($email,$pw)['id']===$logged['id'],'duplicate registration preserves account');
deny(fn()=>$service->login($email,'Otra contraseña ficticia'),'duplicate registration cannot replace password');
$service->register($other,$pw);$service->consumeToken($outbox[$other]['verify'],'verify');$v=$service->login($other,$pw);
$id=$service->book((int)$u['id'],$r);ok(count($service->mine((int)$u['id']))===1,'persistent own list');ok(count($service->agenda($r['day']))>=1,'shared agenda');ok(count($service->mine((int)$v['id']))===0,'own-only list');
deny(fn()=>$service->cancel((int)$v['id'],$id),'foreign cancellation');
deny(fn()=>$service->book((int)$v['id'],array_replace($r,['start'=>'09:30','end'=>'10:30'])),'overlap');
$adjacent=$service->book((int)$v['id'],array_replace($r,['start'=>'10:00','end'=>'11:00']));ok($adjacent>0,'adjacent');
$independent=$service->book((int)$v['id'],array_replace($r,['room_id'=>3]));ok($independent>0,'different room');
ok(array_map('intval',array_column($service->rooms(),'id'))===[1,3],'only two reservable rooms');
deny(fn()=>$service->book((int)$v['id'],array_replace($r,['room_id'=>2])),'retired room blocked');
$service->cancel((int)$u['id'],$id);ok(count($service->mine((int)$u['id']))===0,'cancelled own');
$replacement=$service->book((int)$u['id'],$r);ok($replacement>0,'released slot');
$service->requestToken($email,'reset');$expired=$outbox[$email]['reset'];$db->exec('UPDATE email_tokens SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE user_id='.(int)$u['id']);deny(fn()=>$service->consumeToken($expired,'reset','Otra contraseña ficticia'),'expiry');
$service->requestToken($email,'reset');$first=$outbox[$email]['reset'];$service->requestToken($email,'reset');$second=$outbox[$email]['reset'];deny(fn()=>$service->consumeToken($first,'reset','Otra contraseña ficticia'),'replacement invalidates');
$service->consumeToken($second,'reset','Otra contraseña ficticia');deny(fn()=>$service->login($email,$pw),'old password rejected');$changed=$service->login($email,'Otra contraseña ficticia');ok((int)$changed['auth_version']===(int)$logged['auth_version']+1,'sessions revoked');deny(fn()=>$service->consumeToken($second,'reset','Otra contraseña ficticia'),'reset one use');
$service->throttle('test|'.$suffix,1);deny(fn()=>$service->throttle('test|'.$suffix,1),'throttle');
foreach([$replacement] as $rid)$service->cancel((int)$u['id'],$rid);foreach([$adjacent,$independent]as $rid)$service->cancel((int)$v['id'],$rid);
$q=$db->prepare('DELETE FROM users WHERE id IN (?,?)');$q->execute([$u['id'],$v['id']]);
echo "INTEGRATION: $total comprobaciones correctas; correos simulados, sin SMTP.\n";
