<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aplicación Reserva Salas</title><link rel="stylesheet" href="estilos.css"><link rel="stylesheet" href="corporativo.css"><script src="compartir.js" defer></script><script src="app.js" defer></script></head>
<body>
<header><div class="brand-heading"><img class="official-logo" src="brand/ajuntament-palma-azul.png" alt="Ajuntament de Palma" width="709" height="246"><div class="app-heading"><span class="eyebrow">RESERVA DE SALAS · PERSONAL MUNICIPAL</span><h1>Aplicación Reserva Salas</h1></div></div><?php if ($user): ?><div><span><?=h($user['email'])?></span><form method="post"><?=csrfField()?><input type="hidden" name="action" value="logout"><button class="logout-button" type="submit">Cerrar sesión</button></form></div><?php endif ?></header>
<main>
<?php if ($user): ?><nav aria-label="Pantallas"><a href="?page=agenda" <?=$page==='agenda'?'aria-current="page"':''?>>Salas y disponibilidad</a><a href="?page=mine" <?=$page==='mine'?'aria-current="page"':''?>>Mis reservas</a></nav><?php endif ?>
<?php if ($flash): ?><div id="mensaje" role="status"><?=h($flash)?></div><?php endif ?>
<?php if ($error): ?><p id="error" role="alert"><?=h($error)?></p><?php endif ?>
<?php if (!$user || in_array($page,['verify','reset'],true)): ?>
<section class="login card">
<span class="eyebrow">ACCESO</span>
<?php if ($page==='verify'): ?>
<h2>Verifica tu correo</h2><p>Confirma el enlace recibido para activar tu cuenta. No se realizará una reserva.</p>
<form method="post"><?=csrfField()?><input type="hidden" name="action" value="verify"><button type="submit">Confirmar mi correo</button></form>
<?php elseif ($page==='reset'): ?>
<h2>Nueva contraseña</h2><form method="post"><?=csrfField()?><input type="hidden" name="action" value="reset"><label>Contraseña nueva<input type="password" name="password" autocomplete="new-password" minlength="12" required></label><p class="help">Usa al menos 12 caracteres. Si la contraseña es demasiado larga, te pediremos que la acortes.</p><button type="submit">Guardar contraseña</button></form>
<?php else:
 $titles=['login'=>'Tu próxima reunión, en su sala','register'=>'Crea tu cuenta','forgot'=>'Recupera tu contraseña','resend'=>'Solicita otro enlace de verificación'];
 $actions=['login'=>'login','register'=>'register','forgot'=>'forgot','resend'=>'resend'];
 $labels=['login'=>'Iniciar sesión','register'=>'Registrarme','forgot'=>'Enviar enlace de recuperación','resend'=>'Enviar enlace de verificación'];
?>
<h2><?=h($titles[$page] ?? $titles['login'])?></h2><p>Acceso mediante correo verificado del Ayuntamiento. Solo se admiten direcciones @palma.es.</p>
<form method="post"><?=csrfField()?><input type="hidden" name="action" value="<?=h($actions[$page] ?? 'login')?>">
<label>Correo corporativo<input type="email" name="email" autocomplete="username" maxlength="254" value="<?=h(input('email'))?>" required placeholder="nombre@palma.es"></label>
<?php if (in_array($page,['login','register'],true)): ?><label>Contraseña<input type="password" name="password" autocomplete="<?=$page==='register'?'new-password':'current-password'?>" <?=$page==='register'?'minlength="12"':''?> required></label><?php if ($page==='register'): ?><p class="help">Usa al menos 12 caracteres. Si la contraseña es demasiado larga, te pediremos que la acortes. Recibirás un enlace de un solo uso que caduca en 24 horas.</p><?php endif ?><?php endif ?>
<button type="submit"><?=h($labels[$page] ?? 'Iniciar sesión')?></button></form>
<div class="auth-links"><a href="?page=login">Iniciar sesión</a><a href="?page=register">Crear cuenta</a><a href="?page=forgot">He olvidado mi contraseña</a><a href="?page=resend">Reenviar verificación</a></div>
<?php endif ?>
</section>
<?php elseif ($page==='mine'): ?>
<section><h2>Mis reservas</h2><p>Consulta y cancela tus reservas.</p>
<?php if (!$mine): ?><div class="card">No tienes reservas. Puedes crear una desde Salas y disponibilidad.</div><?php endif ?>
<?php foreach ($mine as $r): ?><article class="card my-item"><div><h3><?=h($r['concept'])?></h3><p><?=h($r['name'])?> · <?=h($r['building'])?></p><p><?=h(date('d/m/Y',strtotime($r['day'])))?> · <?=h(substr($r['starts_at'],0,5))?>–<?=h(substr($r['ends_at'],0,5))?></p></div><form method="post"><?=csrfField()?><input type="hidden" name="action" value="cancel"><input type="hidden" name="reservation_id" value="<?=h($r['id'])?>"><button class="cancel" type="submit" aria-label="Cancelar reserva: <?=h($r['concept'])?>">Cancelar reserva</button></form></article><?php endforeach ?>
</section>
<?php else: $weekday=(int)date('N',strtotime($day))<=5; ?>
<?php $photos=[1=>'images/sala-grande-innovacion.jpeg',3=>'images/sala-otae.jpg']; $locations=[1=>'https://maps.app.goo.gl/eUbJNMvrXMbCu2Gn7',3=>'https://maps.app.goo.gl/kTES8GwQxbLs96GX9']; ?>
<section class="room-carousel" aria-label="Fotografías de las salas" aria-roledescription="carrusel" data-carousel>
<h2 class="sr-only">Conoce las salas</h2>
<div id="room-slides">
<?php foreach ($rooms as $index=>$room): if (!isset($photos[(int)$room['id']])) continue; ?>
<figure class="carousel-slide carousel-room-<?=h($room['id'])?>" data-carousel-slide role="group" aria-roledescription="diapositiva" aria-label="<?=h($index+1)?> de <?=h(count($rooms))?>"><img src="<?=h($photos[(int)$room['id']])?>" alt="Vista de <?=h($room['name'])?>" width="<?=((int)$room['id']===1)?2000:1280?>" height="<?=((int)$room['id']===1)?740:960?>"><figcaption><strong><?=h($room['name'])?></strong><span><?=h($room['building'])?></span></figcaption></figure>
<?php endforeach ?>
</div><div class="carousel-controls" data-carousel-controls hidden><button type="button" class="carousel-prev" data-carousel-prev aria-label="Ver sala anterior" aria-controls="room-slides"><span aria-hidden="true">‹</span></button><span class="carousel-indicator" data-carousel-status role="status" aria-live="polite" aria-atomic="true"></span><button type="button" class="carousel-next" data-carousel-next aria-label="Ver sala siguiente" aria-controls="room-slides"><span aria-hidden="true">›</span></button></div></section>
<section><div class="section-heading"><div><h2>Salas y disponibilidad</h2><p>Lunes a viernes · 07:00–16:00. El concepto de cada reunión es visible para los usuarios autenticados.</p></div></div>
<div class="rooms room-information">
<?php foreach ($rooms as $room): ?><article class="room"><div class="room-head"><h3><?=h($room['name'])?></h3><p class="room-detail"><img class="detail-icon" src="icons/location.svg" alt="" aria-hidden="true" width="18" height="18"><span><?=h($room['building'])?></span></p><p class="room-detail"><img class="detail-icon" src="icons/people.svg" alt="" aria-hidden="true" width="18" height="18"><span>Aforo máximo: <?=h($room['capacity'])?> personas</span></p><p class="room-detail"><img class="detail-icon" src="icons/information.svg" alt="" aria-hidden="true" width="18" height="18"><span><?=h($room['description'])?></span></p><?php if(isset($locations[(int)$room['id']])): ?><a class="location-link" href="<?=h($locations[(int)$room['id']])?>" target="_blank" rel="noopener noreferrer">Ver ubicación<span class="sr-only"> de <?=h($room['name'])?> (se abre en una pestaña nueva)</span></a><?php endif ?></div></article><?php endforeach ?>
</div>
<section class="card booking" id="nueva-reserva"><h2>Nueva reserva</h2><p>Confirmación automática si la sala está disponible. Puedes reservar para el mismo día sin 24 horas de antelación.</p>
<p id="slot-message" role="status" aria-live="polite" hidden></p><form method="post"><?=csrfField()?><input type="hidden" name="action" value="book"><input type="hidden" name="calendar_view" value="<?=h($cal['view']??'week')?>"><div class="fields">
<label>Sala<select id="room_id" name="room_id" required><?php foreach ($rooms as $room): ?><option value="<?=h($room['id'])?>" <?=((input('room_id') ?: (string)($cal['room']??1))===(string)$room['id'])?'selected':''?>><?=h($room['name'])?></option><?php endforeach ?></select></label>
<label class="concept">Nombre o concepto de la reunión<input id="concept" type="text" name="concept" required maxlength="255" value="<?=h(input('concept'))?>" placeholder="Ej. Coordinación del equipo"></label>
<label>Fecha<input id="booking-day" type="date" name="day" value="<?=h(input('day') ?: $day)?>" min="<?=h(date('Y-m-d'))?>" required></label>
<label>Hora de inicio<input id="booking-start" type="time" name="start" min="07:00" max="16:00" value="<?=h(input('start'))?>" required></label>
<label>Hora de fin<input id="booking-end" type="time" name="end" min="07:00" max="16:00" value="<?=h(input('end'))?>" required></label>
</div><button type="submit">Confirmar reserva</button></form></section>
<?php require __DIR__.'/occupancy-view.php'; ?>
</section>
<?php endif ?>
<?php if ($user): ?><section class="card booking"><h2>Normativa de uso</h2><ul><li>Respetar el aforo máximo.</li><li>Dejar la sala ordenada al finalizar.</li><li>Respetar el horario reservado.</li><li>Cancelar la reserva si la reunión no se va a celebrar.</li></ul></section><?php endif ?>
</main><footer>Aplicación Reserva Salas · Ayuntamiento</footer></body></html>
