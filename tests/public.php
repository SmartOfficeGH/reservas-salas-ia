<?php
declare(strict_types=1);
require __DIR__.'/../private/app/bootstrap.php';
$config=require __DIR__.'/../private/config.php';
if($config['environment']!=='local'||!str_ends_with($config['db']['name'],'_test'))throw new RuntimeException('Solo base local de pruebas');
$db=Service::connect($config);$sent=[];
$service=new Service($db,static function($email,$kind,$token)use(&$sent){$sent[$kind]=$token;});
$n=0;function ensure(bool $ok,string $label):void{global $n;if(!$ok)throw new RuntimeException($label);$n++;}
function publicRequest(string $query='',string $method='GET',array $data=[]):array {
    $ctx=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'header'=>'Content-Type: application/x-www-form-urlencoded','content'=>http_build_query($data),'timeout'=>15]]);
    $body=file_get_contents('http://127.0.0.1:8089/ocupacion.php'.$query,false,$ctx);
    return [$body,implode("\n",$http_response_header)];
}
$email='public.test.'.bin2hex(random_bytes(6)).'@palma.es';$concept='PRIVATE-CONCEPT-'.bin2hex(random_bytes(6));$uid=null;
try {
    $service->register($email,'Contraseña ficticia de pruebas');$service->consumeToken($sent['verify'],'verify');
    $uid=(int)$service->login($email,'Contraseña ficticia de pruebas')['id'];
    $day=(new DateTimeImmutable('today',new DateTimeZone('Europe/Madrid')))->modify('+120 days');
    while((int)$day->format('N')>5)$day=$day->modify('+1 day');$day=$day->format('Y-m-d');
    $service->book($uid,['room_id'=>3,'concept'=>$concept,'day'=>$day,'start'=>'09:10','end'=>'09:45']);
    $q=$db->prepare('SELECT id FROM reservations WHERE user_id=?');$q->execute([$uid]);$rid=(int)$q->fetchColumn();
    $safe=$service->publicPeriod($day,$day);$fixture=array_values(array_filter($safe,fn($r)=>(int)$r['room_id']===3&&$r['starts_at']==='09:10:00'));
    ensure(count($fixture)===1&&array_keys($fixture[0])===['room_id','day','starts_at','ends_at'],'minimal DB projection');
    ensure(array_column($service->publicRooms(),'id')===[1,3],'only active rooms');
    foreach(['day','week','month']as $view) {
        [$html,$headers]=publicRequest('?day='.$day.'&view='.$view.'&room=3');
        ensure(str_contains($headers,'200 OK'),'anonymous '.$view);
        ensure(!str_contains($html,$concept)&&!str_contains($html,$email)&&!preg_match('/(?:user_id|reservation_id|data-reservation|data-user|name="concept")/',$html),'no private fields '.$view);
        ensure(!str_contains($html,'data-free-slot')&&!str_contains($html,'nueva-reserva')&&!str_contains($html,'csrf')&&!str_contains($html,'Tu reserva'),'no actions '.$view);
        ensure(str_contains($headers,'frame-ancestors *')&&!str_contains($headers,'X-Frame-Options')&&!str_contains($headers,'Set-Cookie'),'embeddable sessionless '.$view);
        ensure(str_contains($headers,'no-store'),'fresh responses '.$view);
        ensure(str_contains($html,'Ocupado')&&str_contains($html,'Disponible')&&str_contains($html,'Actualizar'),'public states '.$view);
        ensure(!str_contains($html,'SALA PEQUEÑA')&&str_contains($html,'room=3'),'room filter '.$view);
        if($view==='day')ensure(str_contains($html,'Ocupado · 09:10–09:45'),'exact occupied duration');
        if($view==='month')ensure(str_contains($html,'view=day&amp;room=3')&&!str_contains($html,'data-free-slot'),'month to day');
    }
    foreach(['book','cancel','register','login','verify','reset']as $action) {
        [$html,$headers]=publicRequest('','POST',['action'=>$action,'reservation_id'=>$rid,'room_id'=>3,'day'=>$day,'start'=>'10:00','end'=>'11:00','concept'=>'Should not be saved']);
        ensure(str_contains($headers,'405 Method Not Allowed')&&str_contains($headers,'Allow: GET, HEAD'),'reject POST '.$action);
    }
    $q=$db->prepare('SELECT COUNT(*) FROM reservations WHERE user_id=?');$q->execute([$uid]);ensure((int)$q->fetchColumn()===1,'public actions made no writes');
    [$html,$headers]=publicRequest();$today=(new DateTimeImmutable('today',new DateTimeZone('Europe/Madrid')))->format('Y-m-d');
    ensure(str_contains($html,'value="week"')&&str_contains($html,'value="'.$today.'"'),'default current Madrid week');
    [$html]=publicRequest('?view=day&room=all&day='.$day);ensure(substr_count($html,'class="occupation-column"')===2,'both daily rooms');
    [$html]=publicRequest('?view=day&room=1&day='.$day);ensure(substr_count($html,'class="occupation-column"')===1&&!str_contains($html,'Ocupado · 09:10–09:45'),'daily room filter');
    $state=Calendar::state($day,'month',3);$production=['app_url'=>'https://reservasalas.metavisuals.es'];
    $url=Calendar::publicUrl($production,$state,true);ensure(str_starts_with($url,$production['app_url'].'/ocupacion.php?')&&str_contains($url,'view=month&room=3&day='.$day)&&!str_contains($url,'localhost'),'production share URL');
    ensure(!str_contains(Calendar::publicUrl($production,$state,false),'day='),'date omitted uses current period');
    if(($argv[1]??'')==='--browser') {
        passthru('node tests/public-browser.cjs '.escapeshellarg($day),$exitCode);
        ensure($exitCode===0,'public browser checks');
    }
    [$html,$headers]=publicRequest('?view[]=day&room[]=2&day[]=invalid&action=cancel&reservation_id='.$rid);
    ensure(str_contains($headers,'200 OK')&&!str_contains($html,$concept)&&!str_contains($html,$email),'malformed parameters remain safe read only');
    $q=$db->prepare('SELECT COUNT(*) FROM reservations WHERE id=?');$q->execute([$rid]);ensure((int)$q->fetchColumn()===1,'GET action cannot cancel');
    $service->cancel($uid,$rid);[$html]=publicRequest('?view=day&day='.$day.'&room=3');ensure(!str_contains($html,'Ocupado · 09:10–09:45'),'refresh reflects cancellation');
    echo "PUBLIC: $n comprobaciones correctas de privacidad, solo lectura, filtros, enlaces y datos actuales.\n";
} finally {
    if($uid){$q=$db->prepare('DELETE FROM reservations WHERE user_id=?');$q->execute([$uid]);$q=$db->prepare('DELETE FROM users WHERE id=?');$q->execute([$uid]);}
}
