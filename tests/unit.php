<?php
declare(strict_types=1);
require __DIR__.'/../private/app/Service.php';
$count=0;
function check(bool $pass, string $name): void { global $count; if (!$pass) throw new RuntimeException('FAIL: '.$name); $count++; }
function rejects(Closure $f,string $name): void { try { $f(); } catch (UserError) { check(true,$name); return; } throw new RuntimeException('FAIL: '.$name); }
check(Service::email(' Persona@PALMA.ES ')==='persona@palma.es','domain normalization');
foreach (['x@sub.palma.es','x@palma.es.evil.test','x@gmail.com','x@palma.es@evil.test',"x@palma.es\r\nBCC: x@evil.test",'bad','x@palma.es.'] as $email) rejects(fn()=>Service::email($email),'domain rejection');
$hash=Service::hashPassword('Una clave ficticia de prueba');
check(password_verify('Una clave ficticia de prueba',$hash),'password verification');
check(!password_verify('Otra clave ficticia',$hash),'incorrect password');
rejects(fn()=>Service::hashPassword('corta'),'short password');
rejects(fn()=>Service::hashPassword(str_repeat('x',73)),'oversized password');
$now=new DateTimeImmutable('2026-10-05 08:00:00',new DateTimeZone('Europe/Madrid'));
$r=['concept'=>'Reunión','day'=>'2026-10-05','start'=>'09:00','end'=>'10:00'];
Service::validateBooking($r,$now); check(true,'same day');
foreach ([['concept'=>'   '],['concept'=>str_repeat('x',256)],['day'=>'2026-10-04'],['day'=>'2026-10-10'],['day'=>'2026-02-30'],['start'=>'06:59'],['end'=>'16:01'],['end'=>'09:00'],['end'=>'08:59'],['start'=>'07:00'],['start'=>'bad']] as $change) rejects(fn()=>Service::validateBooking(array_replace($r,$change),$now),'booking rejection');
Service::validateBooking(array_replace($r,['day'=>'2026-10-06','start'=>'07:00','end'=>'16:00']),$now); check(true,'full opening hours');
require __DIR__.'/../private/app/bootstrap.php';
check(h('<script>"&')==='&lt;script&gt;&quot;&amp;','HTML escape');
$_SESSION=['csrf'=>'test-csrf']; $_POST=['csrf'=>'test-csrf']; checkCsrf(); check(true,'CSRF accepted');
$_POST=['csrf'=>'bad']; rejects(fn()=>checkCsrf(),'CSRF rejected');
echo "UNIT: $count comprobaciones correctas\n";
