'use strict';
document.querySelectorAll('[data-free-slot]').forEach(button => {
  button.addEventListener('click', () => {
    const message = document.getElementById('slot-message');
    if (button.disabled) return;
    if (new Date(button.dataset.startAt).getTime() < Date.now()) {
      button.disabled = true;
      button.classList.add('unavailable');
      message.textContent = 'Ese horario ya ha pasado. Consulta la agenda y elige otro espacio libre.';
      message.hidden = false;
      message.scrollIntoView({block:'center'});
      return;
    }
    document.getElementById('room_id').value = button.dataset.roomId;
    document.getElementById('booking-day').value = button.dataset.day;
    document.getElementById('booking-start').value = button.dataset.start;
    document.getElementById('booking-end').value = button.dataset.end;
    message.textContent = button.dataset.end
      ? 'Horario preparado con 30 minutos disponibles. Puedes ajustar las horas antes de confirmar.'
      : 'Hora de inicio preparada. No hay 30 minutos seguidos disponibles: indica una hora de fin dentro del hueco libre.';
    message.hidden = false;
    document.getElementById('nueva-reserva').scrollIntoView({block:'start'});
    document.getElementById('concept').focus({preventScroll:true});
  });
});

document.querySelectorAll('[data-carousel]').forEach(carousel => {
  const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
  if (!slides.length) return;
  const status = carousel.querySelector('[data-carousel-status]');
  let current = 0;
  function show(index) {
    current = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => { slide.hidden = i !== current; });
    status.textContent = `${current + 1} de ${slides.length}`;
  }
  carousel.querySelector('[data-carousel-prev]').addEventListener('click', () => show(current - 1));
  carousel.querySelector('[data-carousel-next]').addEventListener('click', () => show(current + 1));
  carousel.querySelector('[data-carousel-controls]').hidden = slides.length < 2;
  show(0);
});
