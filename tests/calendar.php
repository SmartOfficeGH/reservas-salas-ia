<?php
declare(strict_types=1);
require __DIR__.'/../private/app/bootstrap.php';
$n=0;function pass(bool $v,string $label):void{global $n;if(!$v)throw new RuntimeException($label);$n++;}
$s=Calendar::state('2026-10-05');pass($s['view']==='week'&&count($s['days'])===5,'initial week');
pass(Calendar::state('2026-10-06','day')['view']==='day','explicit day');pass(Calendar::state('2026-10-06','month')['view']==='month','explicit month');
$w=Calendar::state('2026-10-11','week',3);pass($w['start']==='2026-10-05'&&$w['end']==='2026-10-09'&&count($w['days'])===5,'Monday Friday');pass($w['prev']==='2026-10-04'&&$w['next']==='2026-10-18','week stepping');
foreach([['2027-01-31','2027-02-28'],['2028-01-31','2028-02-29'],['2026-12-31','2027-01-31']]as [$d,$next])pass(Calendar::state($d,'month',3)['next']===$next,'month boundary');
pass(Calendar::state('2026-03-31','month')['prev']==='2026-02-28','previous month clamp');
pass(count(Calendar::state('2028-02-15','month')['days'])===29,'leap February');
pass(Calendar::state('bad','bad',2)['view']==='week'&&Calendar::state('bad','bad',2)['room']===1,'invalid state fallback');
pass(str_contains(Calendar::url($w,['view'=>'month']),'view=month&room=3'),'filter retained on view change');
$rooms=[['id'=>1,'name'=>'SALA GRANDE INNOVACIÓN'],['id'=>3,'name'=>'SALA OTAE']];$user=['id'=>1];$day='2026-10-06';$weekday=true;$GLOBALS['style_nonce']='test';
$agenda=[['id'=>1,'room_id'=>3,'user_id'=>1,'day'=>$day,'starts_at'=>'09:10:00','ends_at'=>'09:45:00','concept'=>'Exacta OTAE'],['id'=>2,'room_id'=>1,'user_id'=>2,'day'=>$day,'starts_at'=>'10:00:00','ends_at'=>'11:00:00','concept'=>'Otra sala']];
foreach(['day','week','month']as $view){$cal=Calendar::state($day,$view,3);ob_start();require __DIR__.'/../private/app/occupancy-view.php';$html=ob_get_clean();
 if($view==='day'){pass(substr_count($html,'class="occupation-column"')===2,'two day columns');pass(str_contains($html,'Otra sala')&&str_contains($html,'Exacta OTAE'),'both rooms daily');}
 if($view==='week'){pass(substr_count($html,'class="occupation-column"')===5,'five weekdays');pass(!str_contains($html,'Otra sala')&&str_contains($html,'Exacta OTAE'),'weekly room filter');pass(str_contains($html,'data-day="2026-10-06"')&&str_contains($html,'data-room-id="3"'),'weekly slot data');}
 if($view==='month'){pass(!str_contains($html,'data-free-slot')&&!str_contains($html,'occupation-column'),'no monthly time bands');pass(substr_count($html,'class="month-cell')===31,'monthly dates');pass(str_contains($html,'35 min ocupados'),'selected room monthly summary');pass(str_contains($html,'day=2026-10-06&amp;view=day&amp;room=3'),'month opens daily preserving filter');}
}
echo "CALENDAR: $n comprobaciones correctas de vistas, filtros, periodos y resumen mensual.\n";
