<?php
$shareUrl=Calendar::publicUrl($config,$cal,is_string($_GET['day']??null));
$iframe='<iframe src="'.htmlspecialchars($shareUrl,ENT_QUOTES,'UTF-8').'" title="Ocupación pública de salas del Ajuntament de Palma" width="100%" height="1000" loading="lazy"></iframe>';
?>
<section class="share-occupancy" aria-labelledby="share-title" data-share>
<h3 id="share-title">Compartir Ocupación Salas</h3>
<div class="share-actions"><button type="button" class="secondary" id="share-toggle" aria-haspopup="menu" aria-expanded="false" aria-controls="share-menu">Compartir</button>
<div id="share-menu" class="share-menu" role="menu" aria-labelledby="share-toggle" hidden><button type="button" role="menuitem" tabindex="-1" data-copy-target="public-link">Copiar URL</button><button type="button" role="menuitem" tabindex="-1" data-copy-target="public-iframe">Copiar iframe</button></div></div>
<input id="public-link" type="hidden" value="<?=h($shareUrl)?>"><input id="public-iframe" type="hidden" value="<?=h($iframe)?>">
<div id="copy-manual" hidden><label id="copy-manual-label" for="copy-content">Contenido para copiar</label><textarea id="copy-content" rows="4" readonly></textarea><p class="help">Texto seleccionado: usa Copiar, Ctrl+C o el menú de tu dispositivo.</p></div>
<p id="copy-status" role="status" aria-live="polite"></p></section>
