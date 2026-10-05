'use strict';
const share = document.querySelector('[data-share]');
if (share) {
  const toggle = share.querySelector('#share-toggle');
  const menu = share.querySelector('#share-menu');
  const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));
  const status = share.querySelector('#copy-status');
  const manual = share.querySelector('#copy-manual');
  const content = share.querySelector('#copy-content');
  function closeMenu(restoreFocus = false) {
    menu.hidden = true; toggle.setAttribute('aria-expanded', 'false');
    if (restoreFocus) toggle.focus();
  }
  function openMenu(index = 0) {
    menu.hidden = false; toggle.setAttribute('aria-expanded', 'true');
    items[index].focus();
  }
  toggle.addEventListener('click', () => menu.hidden ? openMenu() : closeMenu(true));
  toggle.addEventListener('keydown', event => {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault(); openMenu(event.key === 'ArrowUp' ? items.length - 1 : 0);
    }
  });
  share.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !menu.hidden) { event.preventDefault(); closeMenu(true); }
  });
  menu.addEventListener('keydown', event => {
    const index = items.indexOf(document.activeElement);
    if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
      event.preventDefault();
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
        : (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
      items[next].focus();
    } else if (event.key === 'Tab') closeMenu(true);
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.share-actions')) closeMenu();
  });
  items.forEach(item => item.addEventListener('click', async () => {
    const value = document.getElementById(item.dataset.copyTarget).value;
    const isUrl = item.dataset.copyTarget === 'public-link';
    closeMenu(true); manual.hidden = true; content.value = ''; status.textContent = '';
    try {
      if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
      await navigator.clipboard.writeText(value);
      status.textContent = isUrl ? 'URL copiada' : 'Código iframe copiado';
    } catch {
      share.querySelector('#copy-manual-label').textContent = isUrl ? 'URL para copiar' : 'Código iframe para copiar';
      content.value = value; manual.hidden = false; content.focus(); content.select();
      status.textContent = 'No se pudo copiar automáticamente. Copia manualmente el texto seleccionado.';
    }
  }));
}
