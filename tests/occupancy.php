<?php
declare(strict_types=1);
require __DIR__.'/../private/app/bootstrap.php';
$count=0;
function verify(bool $pass,string $label):void{global $count;if(!$pass)throw new RuntimeException($label);$count++;}
$zone=new DateTimeZone('Europe/Madrid');$now=new DateTimeImmutable('2026-10-05 08:00:00',$zone);
$empty=Occupancy::slots('2026-10-06',[],$now);
verify(count($empty)===18,'18 half-hour rows');verify($empty[0]['suggested_end']==='07:30','first 30 minutes');verify($empty[17]['suggested_end']==='16:00','closing boundary');
$events=[['starts_at'=>'09:10:00','ends_at'=>'09:45:00','concept'=>'Reunión exacta','user_id'=>1]];
$gaps=Occupancy::slots('2026-10-06',$events,$now);
$find=static function(float $start)use($gaps){foreach($gaps as $g)if($g['start']===$start)return $g;return false;};
$short=$find(540.0);verify($short&&$short['end']===550.0&&$short['suggested_end']==='','no invalid 30 minute proposal');
$after=$find(585.0);verify($after&&$after['suggested_end']==='10:15','partial free slot after booking');
verify(!$find(570.0),'occupied half-hour start absent');
foreach($gaps as $gap)verify($gap['end']<=550||$gap['start']>=585,'free blocks do not overlap event');
$past=Occupancy::slots('2026-10-05',[],$now);verify(!$past[0]['allowed']&&$past[2]['allowed'],'past blocked, current future allowed');
foreach(Occupancy::slots('2026-10-10',[],$now)as $gap)verify(!$gap['allowed'],'weekend disabled');
verify(Occupancy::minute('09:10:30')===550.5,'precise seconds geometry');
$rooms=[['id'=>1,'name'=>'SALA GRANDE INNOVACIÓN']];$agenda=[array_replace($events[0],['room_id'=>1])];$day='2026-10-06';$weekday=true;$user=['id'=>1];$GLOBALS['style_nonce']='test-nonce';
ob_start();$cal=Calendar::state($day,'day');require __DIR__.'/../private/app/occupancy-view.php';$html=ob_get_clean();
verify(str_contains($html,'top:24.074074%;height:6.481481%'),'35 minute block exact position and duration');
verify(str_contains($html,'09:10–09:45')&&str_contains($html,'Reunión exacta'),'exact time and concept');
verify(str_contains($html,'nonce="test-nonce"'),'geometry style protected with nonce');
echo "OCCUPANCY: $count comprobaciones correctas de huecos, duración, pasado, fin de semana y representación.\n";
