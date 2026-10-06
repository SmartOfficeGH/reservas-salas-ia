const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const fields={};for(const id of ['room_id','booking-day','booking-start','booking-end','concept','nueva-reserva','slot-message'])fields[id]={value:'original',hidden:true,focus(){this.focused=true},scrollIntoView(){this.scrolled=true}};
const button={disabled:false,dataset:{roomId:'3',day:'2099-10-05',start:'09:00',startAt:'2099-10-05T09:00:00+02:00',end:'09:30'},classList:{add(){}},addEventListener(type,fn){this[type]=fn}};
vm.runInNewContext(fs.readFileSync('public/app.js','utf8'),{document:{getElementById:id=>fields[id],querySelectorAll:s=>s==='[data-free-slot]'?[button]:[]},Date,fetch(){throw Error('No se debe guardar al pulsar')},setInterval(){throw Error('No automatic advance')}});
button.click();assert.equal(fields.room_id.value,'3');assert.equal(fields['booking-day'].value,'2099-10-05');assert.equal(fields['booking-start'].value,'09:00');assert.equal(fields['booking-end'].value,'09:30');assert.equal(fields.concept.value,'original');assert.equal(fields.concept.focused,true);assert.equal(fields['nueva-reserva'].scrolled,true);
button.dataset.end='';button.click();assert.equal(fields['booking-end'].value,'');assert.ok(fields['slot-message'].textContent.includes('No hay 30 minutos'));
button.disabled=true;fields['booking-start'].value='unchanged';button.click();assert.equal(fields['booking-start'].value,'unchanged');
button.disabled=false;button.dataset.startAt='2020-01-01T09:00:00+01:00';button.click();assert.equal(fields['booking-start'].value,'unchanged');assert.equal(button.disabled,true);assert.ok(fields['slot-message'].textContent.includes('ya ha pasado'));
console.log('SLOTS: formulario preparado sin guardar, propuesta condicional, foco, pasado y franjas deshabilitadas comprobados.');

button.disabled=false;button.dataset.startAt="2099-10-05T09:00:00+02:00";button.dataset.roomId="0";button.dataset.end="";button.click();assert.equal(fields.room_id.value,"");assert.equal(fields["booking-end"].value,"");assert.equal(fields.room_id.focused,true);assert.ok(fields["slot-message"].textContent.includes("otras salas pueden estar ocupadas"));
