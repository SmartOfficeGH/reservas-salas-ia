# Reservas Salas - IA

Documento inicial del proyecto · 2 de octubre de 2026

## 1. Objetivo y situación actual

Crear una aplicación interna del Ayuntamiento para consultar la disponibilidad y reservar salas de reuniones. Inicialmente habrá 3 salas, pero la aplicación deberá permitir añadir más salas en el futuro.

El proyecto se encuentra en la fase de definición inicial. La primera versión se centrará en las 3 salas iniciales; la administración y ampliación de salas quedan como posibles mejoras para versiones posteriores.

## 2. Alcance aprobado de la primera versión

### Acceso

- Aplicación web interna para reservar 3 salas de reuniones.
- Acceso exclusivo para personal del Ayuntamiento mediante cuenta corporativa.

### Salas y disponibilidad

- Mostrar el nombre, edificio, aforo y descripción en la ficha de cada sala. El aforo es información visible; no se solicitará el número de asistentes.
- Las salas iniciales serán:
  - SALA GRANDE INNOVACIÓN — Departamento Innovación (Parque Pocoyó). Aforo: 18 personas. «Espacio amplio para reuniones plenarias y presentaciones».
  - SALA PEQUEÑA INNOVACIÓN — Departamento Innovación (Parque Pocoyó). Aforo: 6 personas. «Ideal para reuniones de equipo reducidas y videollamadas».
  - SALA OTAE — Edificio Urbanismo (Avenidas). Aforo: 15 personas. «Ideal para reuniones de trabajo y presentaciones».
- Aplicar estos nombres en la agenda, el formulario y «Mis reservas».
- Consultar la disponibilidad mediante una agenda diaria con una fecha seleccionable y una columna por sala.
- Cada reserva mostrará su horario y el nombre o concepto de la reunión.
- El nombre o concepto de la reunión será visible para todo el personal que acceda a la aplicación.

### Horario

- Las tres salas se podrán reservar de lunes a viernes, de 07:00 a 16:00.
- Las reservas deberán quedar completamente dentro de ese horario.

### Reservas

- Crear reservas asociadas a una sala y al usuario que las realiza, indicando obligatoriamente nombre o concepto de la reunión, fecha, hora de inicio y hora de fin.
- Confirmar automáticamente la reserva si la sala está disponible.
- Permitir reservas para el mismo día si la sala está libre. No se exigen 24 horas de antelación.
- No hay bloqueo por mantenimiento de SALA PEQUEÑA INNOVACIÓN.
- Impedir reservas con horarios solapados en una misma sala.
- Consultar y cancelar las reservas propias.

### Normativa de uso

Mostrar un apartado visible con estas normas:

- Respetar el aforo máximo.
- Dejar la sala ordenada al finalizar.
- Respetar el horario reservado.
- Cancelar la reserva si la reunión no se va a celebrar.

### Pantallas

La primera versión tendrá tres pantallas:

1. Acceso.
2. Salas y disponibilidad.
3. Mis reservas.

## 3. Decisiones pendientes

- Límites de duración y antelación máxima de las reservas. No se exige antelación mínima de 24 horas y se permite reservar para el mismo día.
- Forma de verificar las cuentas corporativas. Microsoft Entra ID es una opción pendiente de validar; el dominio del correo por sí solo no será suficiente para acreditar la identidad.
- Tecnología y alojamiento, que se elegirán más adelante.

## 4. Posibles mejoras para versiones posteriores

- Administración y ampliación de salas.
- Modificación de reservas.
- Reservas recurrentes.
- Notificaciones e integración con calendarios.
- Asistente con IA.
