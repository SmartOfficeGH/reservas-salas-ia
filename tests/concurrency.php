<?php
declare(strict_types=1);
require __DIR__.'/../private/app/Service.php';
$config=require __DIR__.'/../private/config.php';
if($config['environment']!=='local'||!str_ends_with($config['db']['name'],'_test'))exit("Solo base local _test.\n");
$db=Service::connect($config);$service=new Service($db,static function(){});
$suffix=bin2hex(random_bytes(6));$ids=[];$processes=[];
$day=new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Madrid'));while((int)$day->format('N')>5)$day=$day->modify('+1 day');
try {
    for($i=0;$i<2;$i++) {
        $email="concurrent.$suffix.$i@palma.es";
        $service->register($email,'Contraseña ficticia de pruebas');
        $q=$db->prepare('SELECT id FROM users WHERE email=?');$q->execute([$email]);$ids[]=(int)$q->fetchColumn();
    }
    $q=$db->prepare('UPDATE users SET verified_at=UTC_TIMESTAMP() WHERE id IN (?,?)');$q->execute($ids);
    // Retener el mutex de sala mientras se arrancan dos conexiones independientes.
    $db->beginTransaction();$db->query('SELECT id FROM rooms WHERE id=3 FOR UPDATE')->fetch();
    foreach($ids as $id) {
        $pipes=[];$process=proc_open([PHP_BINARY,__DIR__.'/concurrency-worker.php',(string)$id,$day->format('Y-m-d')],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process))throw new RuntimeException('No se pudo iniciar worker');
        fclose($pipes[0]);$processes[]=[$process,$pipes];
    }
    usleep(300000);$db->commit();
    $results=[];
    foreach($processes as [$process,$pipes]) {
        $results[]=stream_get_contents($pipes[1]);$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0||$errors!=='')throw new RuntimeException('Worker error: '.$errors);
    }
    sort($results);
    if($results!==['BOOKED','REJECTED'])throw new RuntimeException('Concurrency failed: '.json_encode($results));
    $q=$db->prepare('SELECT COUNT(*) FROM reservations WHERE user_id IN (?,?)');$q->execute($ids);
    if((int)$q->fetchColumn()!==1)throw new RuntimeException('Incorrect number of saved reservations');
    echo "CONCURRENCY: dos procesos, una reserva confirmada y un solapamiento rechazado.\n";
} finally {
    if($db->inTransaction())$db->rollBack();
    if(count($ids)===2) {
        $q=$db->prepare('DELETE FROM reservations WHERE user_id IN (?,?)');$q->execute($ids);
        $q=$db->prepare('DELETE FROM users WHERE id IN (?,?)');$q->execute($ids);
    }
}
