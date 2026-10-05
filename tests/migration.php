<?php
declare(strict_types=1);
require __DIR__.'/../private/app/Service.php';
$config=require __DIR__.'/../private/config.php';
if($config['environment']!=='local'||!str_ends_with($config['db']['name'],'_test'))exit(2);
// Base nueva y aislada, sin tocar reservas o usuarios de la base de revisión.
$name='reservas_migration_'.bin2hex(random_bytes(5)).'_test';
$admin=new PDO('mysql:host='.$config['db']['host'].';port='.$config['db']['port'],$config['db']['user'],$config['db']['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$admin->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$config['db']['name']=$name;$db=Service::connect($config);
$schema=file_get_contents(__DIR__.'/../database/001_initial.sql');
$legacy=str_replace(",\n is_reservable TINYINT UNSIGNED NOT NULL DEFAULT 1",'',$schema);
if($legacy===$schema)throw new RuntimeException('Legacy fixture failed');
$db->exec($legacy);
$db->exec("INSERT INTO rooms VALUES (2,'SALA PEQUEÑA INNOVACIÓN','Departamento Innovación (Parque Pocoyó)',6,'Ideal para reuniones de equipo reducidas y videollamadas')");
$token='';$service=new Service($db,static function($email,$kind,$raw)use(&$token){$token=$raw;});
$service->register('migration.fixture@palma.es','Contraseña ficticia larga');$service->consumeToken($token,'verify');$user=$service->login('migration.fixture@palma.es','Contraseña ficticia larga');
$q=$db->prepare("INSERT INTO reservations (user_id,room_id,concept,day,starts_at,ends_at) VALUES (?,?,'Historial de prueba','2026-01-05','09:00','10:00')");
foreach([1,2,3]as $id)$q->execute([$user['id'],$id]);
$before=$db->query('SELECT * FROM reservations ORDER BY id')->fetchAll();
$db->exec(file_get_contents(__DIR__.'/../database/002_retire_small_room.sql'));
$after=$db->query('SELECT * FROM reservations ORDER BY id')->fetchAll();
if($before!==$after)throw new RuntimeException('Migration changed reservations');
if(array_map('intval',array_column($service->rooms(),'id'))!==[1,3])throw new RuntimeException('Incorrect active rooms');
if(count($service->mine((int)$user['id']))!==3)throw new RuntimeException('Missing own history');
$day=new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Madrid'));while((int)$day->format('N')>5)$day=$day->modify('+1 day');
try{$service->book((int)$user['id'],['room_id'=>2,'concept'=>'Bloqueada','day'=>$day->format('Y-m-d'),'start'=>'10:00','end'=>'11:00']);throw new RuntimeException('Retired room accepted');}catch(UserError){}
echo "MIGRATION: historial de las tres salas intacto, dos reservables y nueva reserva de sala retirada rechazada.\n";
