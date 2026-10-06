<?php
$isPublic??=false;$cal??=Calendar::state($day);$now=new DateTimeImmutable('now',new DateTimeZone('Europe/Madrid'));
$names=['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];$months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$displayRooms=array_values(array_filter($rooms,fn($r)=>$cal['room']===0||(int)$r['id']===$cal['room']));
$filterName=$cal['room']===0?'Todas las salas':($displayRooms[0]['name']??'Sala');
$eventsFor=static fn($room,$date)=>array_values(array_filter($agenda,fn($e)=>(int)$e['room_id']===(int)$room['id']&&($e['day']??$day)===$date));
$layout=[];$geometry=[];
if($cal['view']!=='month')foreach($cal['view']==='week'?$cal['days']:[$day] as $date) {
    $groups=$cal['view']==='week'?[$displayRooms]:array_map(fn($r)=>[$r],$displayRooms);
    foreach($groups as $group){$lanes=[];foreach($group as $room){
        $events=$eventsFor($room,$date);$gaps=Occupancy::slots($date,$events,$now);
        foreach($gaps as $i=>&$g){$g['id']='gap-'.$room['id'].'-'.str_replace('-','',$date).'-'.$i;$geometry[$g['id']]=[$g['start'],$g['end']];}unset($g);
        foreach($events as $i=>&$e){$e['layout_id']='occupied-'.$room['id'].'-'.str_replace('-','',$date).'-'.$i;$geometry[$e['layout_id']]=[Occupancy::minute($e['starts_at']),Occupancy::minute($e['ends_at'])];}unset($e);
        $lanes[]=['room'=>$room,'events'=>$events,'gaps'=>$gaps];
    }$layout[]=['day'=>$date,'lanes'=>$lanes];}
}
?>
<style nonce="<?=h($GLOBALS['style_nonce']??'')?>"><?php foreach($geometry as $id=>[$from,$to]): ?>#<?=h($id)?>{top:<?=number_format(($from-420)/540*100,6,'.','')?>%;height:<?=number_format(($to-$from)/540*100,6,'.','')?>%}<?php endforeach ?></style>
<section class="occupancy" aria-labelledby="occupation-title"><h2 id="occupation-title">Ocupación de salas</h2>
<?php if($isPublic): ?><form method="get" action="ocupacion.php" class="public-refresh"><input type="hidden" name="view" value="<?=h($cal['view'])?>"><input type="hidden" name="room" value="<?=h($cal['room'])?>"><?php if(is_string($_GET['day']??null)): ?><input type="hidden" name="day" value="<?=h($day)?>"><?php endif ?><button type="submit" class="secondary">Actualizar</button><span class="help">Consultado: <?=h($now->format('d/m/Y H:i'))?> · Europe/Madrid</span></form><?php endif ?>
<nav class="calendar-views" aria-label="Vista del calendario"><?php foreach(['day'=>'Día','week'=>'Semana','month'=>'Mes'] as $key=>$label): ?><a href="<?=h(Calendar::url($cal,['view'=>$key]))?>" <?=$cal['view']===$key?'aria-current="page"':''?>><?=h($label)?></a><?php endforeach ?></nav>
<div class="calendar-toolbar"><div class="period-buttons"><a href="<?=h(Calendar::url($cal,['day'=>$cal['prev']]))?>" aria-label="Periodo anterior">‹ Anterior</a><a href="<?=h(Calendar::url($cal,['day'=>$now->format('Y-m-d')]))?>">Hoy</a><a href="<?=h(Calendar::url($cal,['day'=>$cal['next']]))?>" aria-label="Periodo siguiente">Siguiente ›</a></div>
<form method="get"><?php if(!$isPublic): ?><input type="hidden" name="page" value="agenda"><?php endif ?><input type="hidden" name="view" value="<?=h($cal['view'])?>"><label>Fecha seleccionada<input type="date" name="day" value="<?=h($cal['day'])?>" required></label><label>Filtrar por sala<select name="room"><option value="0" <?=$cal['room']===0?'selected':''?>>Todas las salas</option><?php foreach($rooms as $room): ?><option value="<?=h($room['id'])?>" <?=((int)$room['id']===$cal['room'])?'selected':''?>><?=h($room['name'])?></option><?php endforeach ?></select></label><button type="submit" class="secondary">Consultar</button></form></div>
<p class="calendar-period"><?php if($cal['view']==='month'): ?><?=h($months[(int)date('n',strtotime($day))-1].' '.date('Y',strtotime($day)))?><?php elseif($cal['view']==='week'): ?>Semana del <?=h(date('d/m/Y',strtotime($cal['start'])))?> al <?=h(date('d/m/Y',strtotime($cal['end'])))?><?php else: ?><?=h(date('d/m/Y',strtotime($day)))?><?php endif ?> · <?=h($filterName)?></p>
<div class="room-color-legend" aria-label="Leyenda de salas"><?php foreach($displayRooms as $room): ?><span class="room-color room-color-<?=h($room['id'])?>"><span class="room-swatch" aria-hidden="true"></span><?=h($room['name'])?></span><?php endforeach ?></div>
<?php if($cal['view']==='month'): ?>
<p>Resumen de ocupación por sala. Pulsa una fecha para consultar Día.</p><div class="occupancy-scroll" tabindex="0" role="region" aria-label="Calendario mensual"><div class="month-grid">
<?php foreach($names as $name): ?><div class="month-weekday"><?=h(mb_substr($name,0,3))?></div><?php endforeach ?>
<?php $padding=(int)date('N',strtotime($cal['start']))-1;for($i=0;$i<$padding;$i++): ?><div class="month-empty" aria-hidden="true"></div><?php endfor ?>
<?php foreach($cal['days'] as $date):$closed=(int)date('N',strtotime($date))>5;$past=$date<$now->format('Y-m-d'); ?>
<a class="month-cell <?=($closed||$past)?'month-unavailable':''?>" href="<?=h(Calendar::url($cal,['day'=>$date,'view'=>'day']))?>" <?=($date===$day)?'aria-current="date"':''?> aria-label="<?=h(date('d/m/Y',strtotime($date)).' · '.$filterName.' · Ver ocupación por sala')?>"><strong><?=h(date('j',strtotime($date)))?></strong>
<?php foreach($displayRooms as $room):$events=$eventsFor($room,$date);$minutes=array_sum(array_map(fn($e)=>Occupancy::minute($e['ends_at'])-Occupancy::minute($e['starts_at']),$events));$free=array_sum(array_map(fn($g)=>$g['allowed']?$g['end']-$g['start']:0,Occupancy::slots($date,$events,$now))); ?>
<span class="month-room room-color-<?=h($room['id'])?>"><strong><?=h($room['name'])?></strong><?php if(!$isPublic): ?><span><?=h(count($events))?> reservas</span><?php endif ?><?php if($events): ?><small>Ocupado · <?=h(round($minutes))?> min ocupados</small><?php endif ?><small><?=($closed||$past||$free<=0)?'No reservable':'Disponible · '.h(round($free)).' min'?></small></span>
<?php endforeach ?><small>Ver día</small></a>
<?php endforeach;for($i=0;$i<(7-(($padding+count($cal['days']))%7))%7;$i++): ?><div class="month-empty" aria-hidden="true"></div><?php endfor ?></div></div>
<?php else: ?>
<p>07:00–16:00 · Franjas de 30 minutos. <?=$isPublic?'Consulta de solo lectura.':'Pulsa un hueco para preparar el formulario sin crear la reserva.'?></p>
<?php if($cal['room']===0): ?><p class="help">La disponibilidad se muestra por sala. <?=$isPublic?'Los colores y nombres identifican cada carril.':'Al elegir un hueco, selecciona una sala explícitamente para comprobar su disponibilidad.'?></p><?php endif ?>
<p class="occupancy-legend"><span><?=$isPublic?'○ Disponible':'＋ Libre en esta sala'?></span><span>▰ Ocupado</span><span>— No reservable (pasado o día no permitido)</span></p>
<?php if($cal['view']==='day'&&(int)date('N',strtotime($day))>5): ?><p class="notice">No se puede reservar en esta fecha: el horario es de lunes a viernes.</p><?php endif ?>
<?php if(!$isPublic): ?><noscript><p>Para seleccionar un hueco activa JavaScript, o utiliza el formulario superior.</p></noscript><?php endif ?>
<div class="occupancy-scroll" tabindex="0" role="region" aria-label="Agenda de ocupación. En pantallas estrechas puedes desplazarla horizontalmente."><div class="occupancy-grid <?=$cal['view']==='week'?'week-grid'.($cal['room']===0?' all-rooms-week':''):(count($displayRooms)===1?'single-room-grid':'')?>">
<div class="occupation-axis-heading">Hora</div><?php foreach($layout as $column): ?><h3 class="occupation-heading"><?php if($cal['view']==='week'): ?><?=h($names[(int)date('N',strtotime($column['day']))-1])?><br><?=h(date('d/m',strtotime($column['day'])))?><span class="lane-titles"><?php foreach($column['lanes'] as $lane): ?><small class="room-color-<?=h($lane['room']['id'])?>"><?=h($lane['room']['name'])?></small><?php endforeach ?></span><?php else: ?><?=h($column['lanes'][0]['room']['name'])?><?php endif ?></h3><?php endforeach ?>
<div class="occupation-axis" aria-hidden="true"><?php for($minute=420;$minute<960;$minute+=30): ?><span><?=h(Occupancy::clock($minute))?></span><?php endfor ?><span class="axis-end">16:00</span></div>
<?php foreach($layout as $column): ?><div class="occupation-column" role="group" aria-label="<?=h('Ocupación · '.$column['day'])?>"><?php foreach($column['lanes'] as $lane):$room=$lane['room']; ?><div class="occupation-lane room-color-<?=h($room['id'])?>" role="group" aria-label="<?=h($room['name'])?>">
<?php foreach($lane['gaps'] as $gap):$start=Occupancy::clock($gap['start']);$end=Occupancy::clock($gap['end']);$tag=$isPublic?'div':'button'; ?>
<<?=$tag?> id="<?=h($gap['id'])?>" <?=$isPublic?'tabindex="0"':'type="button"'?> class="occupation-gap <?=$gap['allowed']?'':'unavailable'?>" <?php if(!$isPublic): ?>data-free-slot data-room-id="<?=h($cal['room']===0?0:$room['id'])?>" data-day="<?=h($column['day'])?>" data-start="<?=h($start)?>" data-start-at="<?=h($gap['start_at'])?>" data-end="<?=h($cal['room']===0?'':$gap['suggested_end'])?>" <?=$gap['allowed']?'':'disabled'?><?php endif ?> aria-label="<?=h(($gap['allowed']?($isPublic?'Disponible':'Libre en esta sala'):'No reservable').' · '.$room['name'].' · '.$column['day'].' · '.$start.'–'.$end)?>" title="<?=h($room['name'].' · '.$start.'–'.$end)?>"><span aria-hidden="true"><?=$gap['allowed']?($isPublic?'○ Disponible':'＋ Libre'):'— No reservable'?></span></<?=$tag?>>
<?php endforeach ?>
<?php foreach($lane['events'] as $event):$label=substr($event['starts_at'],0,5).'–'.substr($event['ends_at'],0,5); ?>
<div id="<?=h($event['layout_id'])?>" class="occupation-event" tabindex="0" aria-label="<?=h('Ocupado · '.$room['name'].' · '.$column['day'].' · '.$label.($isPublic?'':' · '.$event['concept']))?>" title="<?=h($room['name'].' · '.$label.($isPublic?'':' · '.$event['concept']))?>"><div class="occupation-event-label"><span class="event-room-name"><?=h($room['name'])?></span><strong>Ocupado · <?=h($label)?></strong><?php if(!$isPublic): ?><span><?=h($event['concept'])?></span><?php if($event['user_id']==$user['id']): ?><small>Tu reserva</small><?php endif ?><?php endif ?></div></div>
<?php endforeach ?></div><?php endforeach ?></div><?php endforeach ?></div></div>
<p class="help">Los bloques respetan las horas exactas. Enfócalos con Tab para leer su contenido completo. <?=$isPublic?'No se publican conceptos ni datos personales.':''?></p>
<?php endif ?>
<?php if(!$isPublic&&isset($config)):require __DIR__.'/share-view.php';endif ?></section>
