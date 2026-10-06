<?php
declare(strict_types=1);
require __DIR__.'/../private/app/bootstrap.php';
$config=require __DIR__.'/../private/config.php';
if($config['environment']!=='local'||!str_ends_with($config['db']['name'],'_test'))throw new RuntimeException('Solo base local de pruebas');
$db=Service::connect($config);$token='';$service=new Service($db,static function($email,$kind,$raw)use(&$token){$token=$raw;});
// Aislar las repeticiones de navegador del límite acumulado por otras pruebas locales.
$q=$db->prepare('DELETE FROM rate_limits WHERE bucket=?');$q->execute([hash('sha256','login|127.0.0.1')]);
$uid=null;$n=0;function jointCheck(bool $ok,string $label):void{global $n;if(!$ok)throw new RuntimeException($label);$n++;}
$email='joint.'.bin2hex(random_bytes(5)).'@palma.es';
try {
    $service->register($email,'Contraseña ficticia prueba conjunta');$service->consumeToken($token,'verify');$uid=(int)$service->login($email,'Contraseña ficticia prueba conjunta')['id'];
    $date=(new DateTimeImmutable('today',new DateTimeZone('Europe/Madrid')))->modify('+150 days');while((int)$date->format('N')>5)$date=$date->modify('+1 day');$day=$date->format('Y-m-d');
    foreach([[1,'09:10','10:15'],[3,'09:30','10:40'],[1,'13:00','14:00'],[3,'13:00','14:00']]as [$room,$start,$end])$service->book($uid,['room_id'=>$room,'concept'=>'PRUEBA CONJUNTA '.$room,'day'=>$day,'start'=>$start,'end'=>$end]);
    jointCheck(!$service->availability(1,$day,'09:20')['available'],'room one occupied');
    $short=$service->availability(3,$day,'09:20');jointCheck($short['available']&&$short['proposed_end']==='','short gap does not propose thirty minutes');
    jointCheck($service->availability(1,$day,'10:15')['proposed_end']==='10:45','adjacent free thirty minutes');
    jointCheck(!$service->availability(3,$day,'10:15')['available'],'different room still occupied');
    jointCheck(!$service->availability(3,$day,'09:20','09:45')['available'],'end overlaps even if start free');
    try{$service->book($uid,['room_id'=>0,'concept'=>'No room','day'=>$day,'start'=>'08:00','end'=>'08:30']);throw new RuntimeException('Missing room accepted');}catch(UserError){jointCheck(true,'explicit room required server side');}
    jointCheck(Calendar::state($day)['room']===0,'initial all rooms');jointCheck(str_contains(Calendar::publicUrl($config,Calendar::state($day),true),'room=0'),'shared filter retained');
    passthru('node tests/joint-browser.cjs '.escapeshellarg($day).' '.escapeshellarg($email),$exitCode);jointCheck($exitCode===0,'joint browser checks');
    echo "JOINT: $n comprobaciones de disponibilidad y filtro correctas.\n";
} finally {if($uid){$q=$db->prepare('DELETE FROM reservations WHERE user_id=?');$q->execute([$uid]);$q=$db->prepare('DELETE FROM users WHERE id=?');$q->execute([$uid]);}}
