/* Demostración local: datos en memoria, sin autenticación ni servicios externos. */
const SALAS = [
  { id: 'grande', nombre: 'SALA GRANDE INNOVACIÓN', edificio: 'Departamento Innovación (Parque Pocoyó)', aforo: 18, descripcion: 'Espacio amplio para reuniones plenarias y presentaciones' },
  { id: 'pequena', nombre: 'SALA PEQUEÑA INNOVACIÓN', edificio: 'Departamento Innovación (Parque Pocoyó)', aforo: 6, descripcion: 'Ideal para reuniones de equipo reducidas y videollamadas' },
  { id: 'otae', nombre: 'SALA OTAE', edificio: 'Edificio Urbanismo (Avenidas)', aforo: 15, descripcion: 'Ideal para reuniones de trabajo y presentaciones' }
];
const USUARIO = 'maria';
function fechaLocal(fecha) {
  return `${fecha.getFullYear()}-${String(fecha.getMonth()+1).padStart(2,'0')}-${String(fecha.getDate()).padStart(2,'0')}`;
}
function fechaLaborableInicial() {
  const fecha = new Date();
  while ([0,6].includes(fecha.getDay())) fecha.setDate(fecha.getDate()+1);
  return fechaLocal(fecha);
}
function esLaborable(fecha) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return false;
  const dia = new Date(`${fecha}T12:00:00`);
  return !Number.isNaN(dia.getTime()) && fechaLocal(dia) === fecha && ![0,6].includes(dia.getDay());
}
function minutos(hora) {
  if (!/^\d{2}:\d{2}$/.test(hora)) return NaN;
  const [h,m] = hora.split(':').map(Number);
  return h < 24 && m < 60 ? h*60+m : NaN;
}
function validarReserva(reserva, existentes) {
  if (!SALAS.some(s=>s.id===reserva.sala)) return 'Selecciona una sala válida.';
  if (!reserva.concepto.trim()) return 'Indica el nombre o concepto de la reunión.';
  if (!esLaborable(reserva.fecha)) return 'Selecciona una fecha de lunes a viernes.';
  const inicio = minutos(reserva.inicio), fin = minutos(reserva.fin);
  if (!Number.isFinite(inicio) || !Number.isFinite(fin)) return 'Indica la hora de inicio y la hora de fin.';
  if (fin <= inicio) return 'La hora de fin debe ser posterior a la hora de inicio.';
  if (inicio < 420 || fin > 960) return 'La reserva debe quedar completamente entre las 07:00 y las 16:00.';
  if (existentes.some(r=>r.sala===reserva.sala && r.fecha===reserva.fecha && inicio < minutos(r.fin) && fin > minutos(r.inicio))) return 'La sala ya está reservada en parte de ese horario. Elige otro horario.';
  return '';
}
const fechaInicial = fechaLaborableInicial();
let reservas = [
  {id:1,sala:'grande',fecha:fechaInicial,inicio:'09:00',fin:'10:00',concepto:'Coordinación de Innovación',usuario:'otro'},
  {id:2,sala:'pequena',fecha:fechaInicial,inicio:'11:00',fin:'12:00',concepto:'Revisión del proyecto',usuario:USUARIO},
  {id:3,sala:'otae',fecha:fechaInicial,inicio:'08:00',fin:'09:30',concepto:'Planificación de Urbanismo',usuario:'otro'}
];
let siguienteId = 4;
const $ = id=>document.getElementById(id);
function elemento(tag, texto, clase) {
  const nodo = document.createElement(tag);
  if (texto !== undefined) nodo.textContent = texto;
  if (clase) nodo.className = clase;
  return nodo;
}
function notificar(texto) { $('mensaje').textContent=texto; $('mensaje').hidden=false; }
function cambiarPantalla(pantalla) {
  $('agenda').hidden=pantalla!=='agenda'; $('mis-reservas').hidden=pantalla!=='mis';
  for (const [id,activa] of [['nav-agenda',pantalla==='agenda'],['nav-mis',pantalla==='mis']]) {
    if (activa) $(id).setAttribute('aria-current','page'); else $(id).removeAttribute('aria-current');
  }
  if(pantalla==='mis') pintarMis(); else pintarAgenda();
}
function pintarAgenda() {
  const fecha=$('fecha-agenda').value, laborable=esLaborable(fecha);
  $('dia-agenda').textContent=fecha ? new Date(`${fecha}T12:00:00`).toLocaleDateString('es-ES',{weekday:'long',day:'numeric',month:'long',year:'numeric'}) : 'Selecciona una fecha para consultar la agenda.';
  $('columnas').replaceChildren();
  for (const sala of SALAS) {
    const columna=elemento('article',undefined,'room'), cabecera=elemento('div',undefined,'room-head');
    cabecera.append(elemento('h3',sala.nombre),elemento('p',sala.edificio),elemento('p',`Aforo máximo: ${sala.aforo} personas`),elemento('p',sala.descripcion));
    const cuerpo=elemento('div',undefined,'room-body');
    cuerpo.append(elemento('p',laborable?'Horario de reserva · 07:00–16:00':'Sin horario de reserva para esta fecha.'));
    const delDia=reservas.filter(r=>r.sala===sala.id&&r.fecha===fecha).sort((a,b)=>a.inicio.localeCompare(b.inicio));
    for(const r of delDia) {
      const tarjeta=elemento('div',undefined,`event${r.usuario===USUARIO?' mine':''}`);
      tarjeta.append(elemento('time',`${r.inicio}–${r.fin}`),elemento('p',r.concepto));
      if(r.usuario===USUARIO) tarjeta.append(elemento('span','Tu reserva','tag'));
      cuerpo.append(tarjeta);
    }
    if (!delDia.length) cuerpo.append(elemento('p',laborable?'Sin reservas. Horario disponible.':'Las salas se reservan de lunes a viernes.','empty'));
    columna.append(cabecera,cuerpo);
    if(laborable) {
      const boton=elemento('button','Reservar esta sala'); boton.type='button';
      boton.addEventListener('click',()=>{ $('sala').value=sala.id; $('fecha-reserva').value=fecha; $('concepto').focus(); });
      columna.append(boton);
    }
    $('columnas').append(columna);
  }
}
function cancelarReserva(id) {
  const reserva=reservas.find(r=>r.id===id&&r.usuario===USUARIO);
  if(!reserva) return false;
  reservas=reservas.filter(r=>r.id!==id); return true;
}
function pintarMis() {
  $('lista-mis').replaceChildren();
  const propias=reservas.filter(r=>r.usuario===USUARIO).sort((a,b)=>(a.fecha+a.inicio).localeCompare(b.fecha+b.inicio));
  if(!propias.length) $('lista-mis').append(elemento('div','No tienes reservas. Puedes crear una desde Salas y disponibilidad.','card'));
  for(const r of propias) {
    const sala=SALAS.find(s=>s.id===r.sala), fila=elemento('article',undefined,'card my-item'), texto=elemento('div');
    texto.append(elemento('h3',r.concepto),elemento('p',`${sala.nombre} · ${sala.edificio}`),elemento('p',`${new Date(r.fecha+'T12:00:00').toLocaleDateString('es-ES')} · ${r.inicio}–${r.fin}`));
    const boton=elemento('button','Cancelar reserva','cancel'); boton.type='button';
    boton.setAttribute('aria-label',`Cancelar reserva: ${r.concepto}`);
    boton.addEventListener('click',()=>{ if(cancelarReserva(r.id)) { pintarMis(); pintarAgenda(); notificar(`Reserva cancelada: ${r.concepto}. El horario vuelve a estar disponible.`); $('nav-mis').focus(); } });
    fila.append(texto,boton); $('lista-mis').append(fila);
  }
}
function iniciar() {
  $('fecha-agenda').value=fechaInicial; $('fecha-reserva').value=fechaInicial;
  for(const sala of SALAS) { const opcion=elemento('option',sala.nombre); opcion.value=sala.id; $('sala').append(opcion); }
  $('entrar').addEventListener('click',()=>{ $('acceso').hidden=true; $('aplicacion').hidden=false; $('identidad').hidden=false; cambiarPantalla('agenda'); $('nav-agenda').focus(); });
  $('nav-agenda').addEventListener('click',()=>{ $('mensaje').hidden=true; cambiarPantalla('agenda'); });
  $('nav-mis').addEventListener('click',()=>{ $('mensaje').hidden=true; cambiarPantalla('mis'); });
  $('fecha-agenda').addEventListener('change',()=>{ $('fecha-reserva').value=$('fecha-agenda').value; pintarAgenda(); });
  $('form-reserva').addEventListener('submit',evento=>{
    evento.preventDefault(); $('mensaje').hidden=true;
    const reserva={sala:$('sala').value,concepto:$('concepto').value.trim(),fecha:$('fecha-reserva').value,inicio:$('inicio').value,fin:$('fin').value,usuario:USUARIO};
    const error=validarReserva(reserva,reservas); $('error').textContent=error; $('error').hidden=!error;
    if(error) return;
    reservas.push({...reserva,id:siguienteId++}); $('fecha-agenda').value=reserva.fecha; pintarAgenda();
    notificar(`Reserva confirmada: ${reserva.concepto} · ${SALAS.find(s=>s.id===reserva.sala).nombre} · ${new Date(reserva.fecha+'T12:00:00').toLocaleDateString('es-ES')} · ${reserva.inicio}–${reserva.fin}. Puedes consultarla en Mis reservas.`);
    $('concepto').value=''; $('inicio').value=''; $('fin').value='';
    $('mensaje').scrollIntoView({block:'nearest'});
  });
}
if (typeof document !== 'undefined') iniciar();
if (typeof module !== 'undefined') module.exports={validarReserva,esLaborable,minutos,cancelarReserva,getReservas:()=>reservas,fechaInicial};
