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
    const joint = button.dataset.roomId === '0';
    document.getElementById('room_id').value = joint ? '' : button.dataset.roomId;
    document.getElementById('booking-day').value = button.dataset.day;
    document.getElementById('booking-start').value = button.dataset.start;
    document.getElementById('booking-end').value = button.dataset.end;
    message.textContent = joint ? 'Fecha y hora preparadas. Elige una sala para consultar su disponibilidad; otras salas pueden estar ocupadas.' : button.dataset.end
      ? 'Horario preparado con 30 minutos disponibles. Puedes ajustar las horas antes de confirmar.'
      : 'Hora de inicio preparada. No hay 30 minutos seguidos disponibles: indica una hora de fin dentro del hueco libre.';
    message.hidden = false;
    document.getElementById('nueva-reserva').scrollIntoView({block:'start'});
    const form = document.querySelector?.('#nueva-reserva form');
    if (form) form.dataset.checkAvailability = joint ? 'true' : 'false';
    document.getElementById(joint ? 'room_id' : 'concept').focus({preventScroll:true});
  });
});

const bookingForm = document.querySelector?.('#nueva-reserva form');
if (bookingForm) {
  let revision = 0;
  const fields = ['room_id','booking-day','booking-start','booking-end'].map(id=>document.getElementById(id));
  async function checkAvailability() {
    if (bookingForm.dataset.checkAvailability !== 'true') return;
    const current = ++revision;
    const [room,day,start,end] = fields.map(field=>field.value);
    const message = document.getElementById('slot-message');
    if (!room || !day || !start) { message.textContent='Elige una sala, fecha y hora para comprobar disponibilidad.';message.hidden=false;return; }
    message.textContent='Consultando disponibilidad de la sala elegida…';message.hidden=false;
    try {
      const query = new URLSearchParams({page:'availability',room,day,start,end});
      const response = await fetch('index.php?'+query.toString(),{credentials:'same-origin',cache:'no-store'});
      const result = await response.json();
      if (current !== revision || fields.map(field=>field.value).join('|') !== [room,day,start,end].join('|') || bookingForm.dataset.checkAvailability !== 'true') return;
      if (result.available && !end && result.proposed_end) fields[3].value=result.proposed_end;
      message.textContent=result.message+(result.available&&!end?(result.proposed_end?' Se proponen 30 minutos para esta sala.':' No hay 30 minutos seguidos: ajusta la hora de fin.'):'');
    } catch {
      if(current===revision&&fields.map(field=>field.value).join('|')===[room,day,start,end].join('|')&&bookingForm.dataset.checkAvailability==='true')message.textContent='No se ha podido consultar la disponibilidad. Vuelve a elegir la sala; la reserva se comprobará también al confirmar.';
    }
  }
  fields.forEach(field=>field.addEventListener('change',checkAvailability));
}

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
