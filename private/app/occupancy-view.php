<?php
$cal??=Calendar::state($day);$now=new DateTimeImmutable('now',new DateTimeZone('Europe/Madrid'));$layout=[];$geometry=[];
$names=['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$selectedRoom=array_values(array_filter($rooms,fn($r)=>(int)$r['id']===$cal['room']))[0]??$rooms[0];
foreach($cal['view']==='month'?[]:$cal['days']as $columnDay)foreach($cal['view']==='week'?[$selectedRoom]:$rooms as $room) {
    $events=array_values(array_filter($agenda,fn($r)=>(int)$r['room_id']===(int)$room['id']&&($r['day']??$day)===$columnDay));
    $gaps=Occupancy::slots($columnDay,$events,$now);
    foreach($gaps as $i=>&$gap){$gap['id']='gap-'.(int)$room['id'].'-'.str_replace('-','',$columnDay).'-'.$i;$geometry[$gap['id']]=[$gap['start'],$gap['end']];}unset($gap);
    foreach($events as $i=>&$event){$event['layout_id']='occupied-'.(int)$room['id'].'-'.str_replace('-','',$columnDay).'-'.$i;$geometry[$event['layout_id']]=[Occupancy::minute($event['starts_at']),Occupancy::minute($event['ends_at'])];}unset($event);
    $layout[]=['room'=>$room,'day'=>$columnDay,'gaps'=>$gaps,'events'=>$events];
}
?>
<style nonce="<?=h($GLOBALS['style_nonce']??'')?>">
<?php foreach($geometry as $id=>[$from,$to]): ?>
#<?=h($id)?>{top:<?=number_format(($from-420)/540*100,6,'.','')?>%;height:<?=number_format(($to-$from)/540*100,6,'.','')?>%}
<?php endforeach ?>
</style>
<section class="occupancy" aria-labelledby="occupation-title">
<h2 id="occupation-title">Ocupación de salas</h2>
<nav class="calendar-views" aria-label="Vista del calendario">
<?php foreach(['day'=>'Día','week'=>'Semana','month'=>'Mes']as $key=>$label): ?><a href="<?=h(Calendar::url($cal,['view'=>$key]))?>" <?=$cal['view']===$key?'aria-current="page"':''?>><?=h($label)?></a><?php endforeach ?>
</nav>
<div class="calendar-toolbar"><div class="period-buttons"><a href="<?=h(Calendar::url($cal,['day'=>$cal['prev']]))?>" aria-label="Periodo anterior">‹ Anterior</a><a href="<?=h(Calendar::url($cal,['day'=>date('Y-m-d')]))?>">Hoy</a><a href="<?=h(Calendar::url($cal,['day'=>$cal['next']]))?>" aria-label="Periodo siguiente">Siguiente ›</a></div>
<form method="get"><input type="hidden" name="page" value="agenda"><input type="hidden" name="view" value="<?=h($cal['view'])?>"><label>Fecha seleccionada<input type="date" name="day" value="<?=h($cal['day'])?>" required></label>
<?php if($cal['view']!=='day'): ?><label>Filtrar por sala<select name="room"><?php foreach($rooms as $room): ?><option value="<?=h($room['id'])?>" <?=((int)$room['id']===$cal['room'])?'selected':''?>><?=h($room['name'])?></option><?php endforeach ?></select></label><?php else: ?><input type="hidden" name="room" value="<?=h($cal['room'])?>"><?php endif ?><button type="submit" class="secondary">Consultar</button></form></div>
<p class="calendar-period"><?php if($cal['view']==='month'): ?><?=h($months[(int)date('n',strtotime($cal['day']))-1].' '.date('Y',strtotime($cal['day'])))?><?php elseif($cal['view']==='week'): ?>Semana del <?=h(date('d/m/Y',strtotime($cal['start'])))?> al <?=h(date('d/m/Y',strtotime($cal['end'])))?> · <?=h($selectedRoom['name'])?><?php else: ?><?=h(date('d/m/Y',strtotime($cal['day'])))?> · Ambas salas<?php endif ?></p>
<?php if($cal['view']==='month'): ?>
<p>Resumen por día de <?=h($selectedRoom['name'])?>. Pulsa una fecha para abrir Día y elegir una hora.</p>
<div class="occupancy-scroll" tabindex="0" role="region" aria-label="Calendario mensual"><div class="month-grid">
<?php foreach($names as $name): ?><div class="month-weekday"><?=h(mb_substr($name,0,3))?></div><?php endforeach ?>
<?php $padding=(int)date('N',strtotime($cal['start']))-1;for($i=0;$i<$padding;$i++): ?><div class="month-empty" aria-hidden="true"></div><?php endfor ?>
<?php foreach($cal['days']as $cellDay):
$events=array_values(array_filter($agenda,fn($r)=>(int)$r['room_id']===$cal['room']&&$r['day']===$cellDay));
$minutes=array_sum(array_map(fn($r)=>Occupancy::minute($r['ends_at'])-Occupancy::minute($r['starts_at']),$events));
$closed=(int)date('N',strtotime($cellDay))>5;$past=$cellDay<date('Y-m-d');
?><a class="month-cell <?=($closed||$past)?'month-unavailable':''?>" href="<?=h(Calendar::url($cal,['day'=>$cellDay,'view'=>'day']))?>" <?=($cellDay===$cal['day'])?'aria-current="date"':''?> aria-label="<?=h(date('d/m/Y',strtotime($cellDay)).' · '.$selectedRoom['name'].' · '.count($events).' reservas'.($closed?' · No reservable: fin de semana':($past?' · Fecha pasada':'')))?>"><strong><?=h(date('j',strtotime($cellDay)))?></strong><span><?=h(count($events))?> reservas</span><?php if($events): ?><small><?=h(round($minutes))?> min ocupados</small><?php endif ?><small><?=$closed?'No reservable':($past?'Fecha pasada':'Ver día')?></small></a>
<?php endforeach;for($i=0;$i<(7-(($padding+count($cal['days']))%7))%7;$i++): ?><div class="month-empty" aria-hidden="true"></div><?php endfor ?>
</div></div>
<?php else: ?>
<p>07:00–16:00 · Franjas de 30 minutos. Pulsa un espacio libre para preparar una reserva en el formulario superior.</p>
<p class="occupancy-legend"><span>＋ Libre</span><span>▰ Ocupado</span><span>— No reservable (pasado o día no permitido)</span></p>
<?php if($cal['view']==='day'&&(int)date('N',strtotime($cal['day']))>5): ?><p class="notice">No se puede reservar en esta fecha: el horario es de lunes a viernes.</p><?php endif ?>
<noscript><p>Para seleccionar un hueco activa JavaScript, o utiliza el formulario superior.</p></noscript>
<div class="occupancy-scroll" tabindex="0" role="region" aria-label="Agenda de ocupación. En pantallas estrechas puedes desplazarla horizontalmente."><div class="occupancy-grid <?=$cal['view']==='week'?'week-grid':''?>">
<div class="occupation-axis-heading">Hora</div>
<?php foreach($layout as $column): ?><h3 class="occupation-heading"><?php if($cal['view']==='week'): ?><?=h($names[(int)date('N',strtotime($column['day']))-1])?><br><?=h(date('d/m',strtotime($column['day'])))?><?php else: ?><?=h($column['room']['name'])?><?php endif ?></h3><?php endforeach ?>
<div class="occupation-axis" aria-hidden="true"><?php for($minute=420;$minute<960;$minute+=30): ?><span><?=h(Occupancy::clock($minute))?></span><?php endfor ?><span class="axis-end">16:00</span></div>
<?php foreach($layout as $column): $room=$column['room']; ?>
<div class="occupation-column" role="group" aria-label="<?=h('Ocupación de '.$room['name'].' · '.$column['day'])?>">
<?php foreach($column['gaps']as $gap): $start=Occupancy::clock($gap['start']);$end=Occupancy::clock($gap['end']); ?>
<button id="<?=h($gap['id'])?>" type="button" class="occupation-gap <?=$gap['allowed']?'':'unavailable'?>" data-free-slot data-room-id="<?=h($room['id'])?>" data-day="<?=h($column['day'])?>" data-start="<?=h($start)?>" data-start-at="<?=h($gap['start_at'])?>" data-end="<?=h($gap['suggested_end'])?>" <?=$gap['allowed']?'':'disabled'?> aria-label="<?=h(($gap['allowed']?'Libre':'No reservable').' · '.$room['name'].' · '.$column['day'].' · '.$start.'–'.$end)?>" title="<?=h(($gap['allowed']?'Libre':'No reservable').' '.$start.'–'.$end)?>"><span aria-hidden="true"><?=$gap['allowed']?'＋ Libre':'— No reservable'?></span></button>
<?php endforeach ?>
<?php foreach($column['events']as $event): $label=substr($event['starts_at'],0,5).'–'.substr($event['ends_at'],0,5); ?>
<div id="<?=h($event['layout_id'])?>" class="occupation-event" tabindex="0" aria-label="<?=h('Ocupado · '.$room['name'].' · '.$column['day'].' · '.$label.' · '.$event['concept'])?>" title="<?=h($label.' · '.$event['concept'])?>"><div class="occupation-event-label"><strong>Ocupado · <?=h($label)?></strong><span><?=h($event['concept'])?></span><?php if($event['user_id']==$user['id']): ?><small>Tu reserva</small><?php endif ?></div></div>
<?php endforeach ?>
</div><?php endforeach ?>
</div></div>
<p class="help">Las reservas mantienen sus horas exactas. En bloques cortos, enfoca el bloque con Tab o pasa el cursor para leer el concepto completo.</p>
<?php endif ?>
</section>
