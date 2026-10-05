<?php
declare(strict_types=1);
require __DIR__.'/../private/app/Service.php';
$config=require __DIR__.'/../private/config.php';
if($config['environment']!=='local'||!str_ends_with($config['db']['name'],'_test'))exit(2);
$service=new Service(Service::connect($config),static function(){throw new RuntimeException('No mail');});
try {
    $service->book((int)$argv[1],['room_id'=>3,'concept'=>'Concurrent test','day'=>$argv[2],'start'=>'13:00','end'=>'14:00']);
    echo 'BOOKED';
} catch(UserError $e){echo 'REJECTED';}
