# Reservas Salas - IA

Documento inicial del proyecto · 2 de octubre de 2026

Actualizado para la aplicación PHP/MySQL · 5 de octubre de 2026

## 1. Objetivo y situación actual

Crear una aplicación interna del Ayuntamiento para consultar la disponibilidad y reservar salas de reuniones. Se partió de 3 salas; actualmente quedan 2 reservables porque SALA PEQUEÑA INNOVACIÓN se ha convertido en despacho. La aplicación deberá permitir añadir más salas en el futuro.

La primera versión se centra en las 2 salas reservables; la administración y ampliación de salas quedan como posibles mejoras para versiones posteriores. Tras validar el prototipo, se prepara una aplicación web funcional con PHP 8.4 y MySQL para Webempresa. La aplicación ya está desplegada. Las ampliaciones se preparan y revisan localmente antes de autorizar su actualización en el hosting.

## 2. Alcance aprobado de la primera versión

### Acceso

- Aplicación web interna para reservar 2 salas de reuniones.
- Acceso exclusivo para personal del Ayuntamiento mediante cuenta corporativa.
- Como sustitución provisional de Microsoft Entra ID, registro exclusivamente con direcciones cuyo dominio sea exactamente palma.es y verificación de la posesión del buzón mediante un enlace de un solo uso que caduca en 24 horas. El dominio por sí solo no acredita identidad; la verificación comprueba acceso al buzón, no la situación laboral del usuario.
- No se permite acceder a la agenda ni reservar sin verificar el correo.
- Inicio y cierre de sesión y recuperación de contraseña mediante enlace de un solo uso que caduca en 30 minutos. Contraseñas almacenadas con hash seguro.

### Salas y disponibilidad

- Mostrar el nombre, edificio, aforo y descripción en la ficha de cada sala. El aforo es información visible; no se solicitará el número de asistentes.
- Las salas iniciales serán:
  - SALA GRANDE INNOVACIÓN — Departamento Innovación (Parque Pocoyó). Aforo: 18 personas. «Espacio amplio para reuniones plenarias y presentaciones».
  - SALA OTAE — Edificio Urbanismo (Avenidas). Aforo: 15 personas. «Ideal para reuniones de trabajo y presentaciones».
- Aplicar estos nombres en la agenda, el formulario y «Mis reservas».
- SALA PEQUEÑA INNOVACIÓN queda retirada de la agenda y del formulario. El servidor impide nuevas reservas. Las reservas anteriores se conservan con su sala original y se consultan en «Mis reservas» por su propietario; no se eliminan durante la actualización.
- Los datos iniciales incluyen solo las dos salas reservables. Una migración para bases existentes marca la sala retirada como no reservable sin borrar salas, usuarios ni reservas.
- Sobre la agenda, tras iniciar sesión, mostrar un carrusel compacto con las fotos originales de SALA GRANDE INNOVACIÓN y SALA OTAE, nombre y edificio, controles anterior/siguiente accesibles y sin avance automático. Las imágenes se adaptan a móvil y escritorio sin deformarse.
- Cada ficha incluye «Ver ubicación» en una pestaña nueva: SALA GRANDE INNOVACIÓN https://maps.app.goo.gl/eUbJNMvrXMbCu2Gn7 y SALA OTAE https://maps.app.goo.gl/kTES8GwQxbLs96GX9.
- Consultar la disponibilidad en una única agenda diaria «Ocupación de salas», con selector Día · Semana · Mes y Semana como vista inicial cuando no se indica otra vista, mostrando la semana de la fecha actual en Europe/Madrid. Día muestra dos columnas y franjas de 30 minutos de 07:00 a 16:00; El filtro inicial es Todas las salas. Semana muestra de lunes a viernes todas las salas en carriles paralelos dentro de cada día, o una sala concreta al filtrarla; Mes muestra un resumen por día identificado por sala, sin franjas dentro de las casillas. Las reservas se dibujan con posición y duración reales, aunque sus horas no coincidan con las franjas.
- Cada bloque ocupado autenticado muestra nombre de sala, inicio, fin y concepto. Cada sala tiene un color diferenciado y una leyenda; las reservas simultáneas o parcialmente coincidentes de salas distintas quedan lado a lado, sin taparse. Los huecos libres y ocupados se distinguen mediante etiquetas y formas además del color. En bloques cortos se puede leer toda la información enfocándolos con el teclado o pasando el cursor.
- El nombre o concepto de la reunión será visible para todo el personal que acceda a la aplicación.

### Horario

- Las dos salas reservables se podrán reservar de lunes a viernes, de 07:00 a 16:00.
- Las reservas deberán quedar completamente dentro de ese horario.

### Reservas

- Crear reservas asociadas a una sala y al usuario que las realiza, indicando obligatoriamente nombre o concepto de la reunión, fecha, hora de inicio y hora de fin.
- Con una sala filtrada, pulsar un hueco libre prepara el formulario existente con esa sala, fecha y hora de inicio. En Todas las salas prepara fecha e inicio, deja la sala sin seleccionar y exige elegirla explícitamente; entonces consulta su disponibilidad en el servidor. Solo propone 30 minutos si esa sala tiene disponibilidad continua suficiente; en otro caso deja el fin pendiente. Un hueco libre de una sala no implica que las demás estén libres. Lleva al usuario al formulario y permite ajustar las horas antes de confirmar. El clic no crea la reserva.
- Los horarios pasados y los días no permitidos no permiten iniciar reservas desde la agenda. Las validaciones del servidor siguen siendo obligatorias, incluso si cambió la disponibilidad después de consultar.
- Tras el carrusel, el orden es: fichas de Salas y disponibilidad, formulario Nueva reserva siempre visible, calendario Ocupación de salas y Normativa de uso.
- Se puede reservar directamente desde el formulario o preparar sus datos desde un hueco libre de Día o Semana. Mes abre Día al pulsar una fecha, conservando el filtro; Todas las salas exige elegir una sala en el formulario.
- Navegación al periodo anterior y siguiente y botón Hoy; la fecha, vista y filtro se conservan sincronizados en los controles. Las tres vistas incluyen filtro Todas las salas o una sala concreta. El cambio de mes ajusta fechas como el día 31 al último día válido del mes de destino.
- No se muestra una segunda lista o agenda de las mismas reservas. Las fichas mantienen ubicación, aforo, descripción y enlaces de mapa. La agenda permite usar teclado y desplazamiento horizontal en móviles estrechos.
- Confirmar automáticamente la reserva si la sala está disponible.
- Permitir reservas para el mismo día si la sala está libre. No se exigen 24 horas de antelación.
- SALA PEQUEÑA INNOVACIÓN ya no es reservable porque ahora es un despacho.
- Impedir reservas con horarios solapados en una misma sala.
- Guardar las reservas permanentemente en MySQL y validar en el servidor incluso ante solicitudes simultáneas.
- La hora de fin debe ser posterior a la de inicio y no se permiten reservas cuyo inicio esté en el pasado, según Europe/Madrid.
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

### Tecnología y destino aprobados

- PHP 8.4 y MySQL con tablas InnoDB, consultas preparadas y transacciones para evitar solapamientos simultáneos.
- Sesiones protegidas y formularios con protección contra solicitudes falsificadas.
- URL prevista: https://reservasalas.metavisuals.es.
- Carpeta pública: /home/delanada/public_html/reservasalas.metavisuals.es.
- Base de datos: delanada_reservas_sala. Usuario: delanada_reservas_app.
- Remitente SMTP de Gmail: smartofficepalma@gmail.com. Zona horaria: Europe/Madrid.
- Configuración y dependencias privadas fuera de la carpeta pública. Credenciales introducidas solo en el servidor y excluidas de Git.

## 3. Decisiones pendientes

- Límites de duración y antelación máxima de las reservas. No se exige antelación mínima de 24 horas y se permite reservar para el mismo día.
- Documentar la versión exacta del servidor MySQL y completar las revisiones operativas y de seguridad pendientes. La conexión funcional con localhost:3306 y los correos de verificación y recuperación ya están comprobados en Webempresa según la validación manual del usuario.
- Ampliar las comprobaciones de entrega en buzones palma.es y los demás recorridos en Webempresa. El primer envío y verificación funcionaron según la validación manual comunicada por el usuario; la causa de su retraso aproximado de tres minutos queda sin determinar.

## 4. Posibles mejoras para versiones posteriores

- Administración y ampliación de salas.
- Modificación de reservas.
- Reservas recurrentes.
- Notificaciones e integración con calendarios.
- Asistente con IA.

## Identidad visual aprobada

Logotipo oficial del Ajuntament de Palma, sin modificaciones ni cambios de proporción, y paleta de los PDF suministrados: azul #09548A, negro #191816, gris #7C8687 y turquesa #00B3CB. Fuentes Open Sans para texto y Poppins para títulos, extraídas de los CSS suministrados. Tema coherente para todas las pantallas; adaptación y limitaciones en IDENTIDAD-CORPORATIVA.md. No cambia el alcance ni las reglas de reserva.

## Fichas y vista inicial actualizadas

Las fichas incorporan iconos SVG locales de ubicación, personas e información, con trazo común y azul corporativo. Son decorativos (texto alternativo vacío y aria-hidden); se conservan edificio, aforo, descripción y mapas. No hay dependencias externas.

Al abrir la agenda sin `view`, se muestra Semana. Sin `day`, se usa la fecha actual de Europe/Madrid. Las vistas explícitas Día, Semana y Mes se respetan; Mes sigue abriendo Día al elegir una fecha. Se mantienen sala filtrada, navegación y preparación del formulario desde huecos libres.

## Nombre visible

El nombre mostrado en todas las pantallas, el título de las pestañas y los correos de verificación y recuperación es «Aplicación Reserva Salas». Se conservan los nombres de carpeta, repositorio y archivo de definición.

## Consulta pública aprobada

Además de las pantallas autenticadas, se ofrece una vista pública de solo lectura para compartir o insertar en otra web. Consulta únicamente las dos salas activas, fecha, horarios y estados Disponible, Ocupado y No reservable. No publica conceptos, nombres, correos ni identificadores de reservas, tampoco en HTML o atributos. Día, Semana (inicial) y Mes permiten navegar y filtrar; Todas las salas es el filtro inicial en las tres vistas; también se permite una sala concreta. Día conserva columnas por sala, Semana carriles paralelos dentro de cada día y Mes un resumen identificado por sala. Los colores, la leyenda y el nombre identifican las salas sin publicar conceptos ni información privada. Sin fecha explícita se muestra el periodo actual en Europe/Madrid. Actualizar vuelve a consultar el servidor. No contiene formulario de reserva ni acciones sobre huecos.

Al final del calendario autenticado se añade «Compartir Ocupación Salas», con un único botón «Compartir» y un menú con «Copiar URL» y «Copiar iframe», conservando vista, sala y fecha cuando se ha elegido. Usa la URL configurada e iframe con título accesible. Tras elegir, el menú se cierra y confirma la copia. Solo si falla el portapapeles muestra el contenido seleccionado para copia manual; el menú permite teclado, Escape y clic fuera. Solo la entrada pública permite ser insertada; las pantallas autenticadas conservan sus protecciones. La creación y cancelación de reservas siguen requiriendo correo verificado y permisos existentes.

## Consulta conjunta — PASO 13 (6 de octubre de 2026)

Los enlaces e iframe conservan `room=0` para Todas las salas, además de vista y fecha explícita. Semana sigue siendo inicial y sin fecha se utiliza el periodo actual de Europe/Madrid. La consulta previa de disponibilidad no guarda reservas ni sustituye las validaciones transaccionales al confirmar. La vista pública permanece de solo lectura y no carga conceptos, usuarios ni identificadores de reservas. No requiere cambios de base de datos.

## Recursos y actualización de estilos

Los CSS y JavaScript públicos utilizan URLs versionadas mediante SHA-256 del contenido en las pantallas autenticadas y en la consulta pública. Se evita reutilizar una versión antigua tras sustituir recursos, conservando las políticas de seguridad y las reglas de reserva. Este ajuste técnico no cambia datos ni funcionalidades.

## Resultado comunicado tras el despliegue

Validación manual comunicada por el usuario el 6 de octubre de 2026: la corrección de caché está desplegada en Webempresa y las salas se muestran separadas, con colores distintos y sin superposición. La vista pública conserva la privacidad y no permite reservar. Esta validación en el hosting la realizó el usuario; el agente no efectuó cambios en el servidor.

El primer correo de verificación tardó aproximadamente tres minutos en llegar. El envío y la verificación mediante el enlace funcionaron. No se ha determinado la causa del retraso; esta observación no establece un tiempo de entrega garantizado ni demuestra que el retraso proceda de Gmail, Webempresa o el buzón receptor.

## Cierre del primer despliegue — 7 de octubre de 2026

Estado: primer despliegue funcional validado manualmente en Webempresa, según las comprobaciones comunicadas por el usuario. El agente documenta los resultados; no ha realizado estas operaciones en el servidor.

| Comprobación en hosting | Resultado comunicado |
|---|---|
| Registro, recepción del correo y verificación | Correctos. El primer correo tardó aproximadamente tres minutos; causa del retraso no determinada. |
| Inicio de sesión y recuperación de contraseña | Correctos; el correo de recuperación llegó rápidamente, sin medición exacta comunicada. |
| Persistencia de reservas y cancelación | Funcionamiento comprobado. |
| Usuario MySQL de la aplicación | Limitado a SELECT, INSERT, UPDATE y DELETE; funcionamiento comprobado con esos privilegios. |
| Ocupación conjunta | Colores diferenciados y carriles separados, sin superposición. |
| Consulta pública | Sin conceptos ni datos personales y sin posibilidad de reservar. |
| Inserción mediante iframe | Probada correctamente en otra web. |

Las conexiones reales de la aplicación a MySQL y el envío/recepción de los correos de verificación y recuperación quedan comprobados funcionalmente. No equivalen a una auditoría independiente de TLS, permisos de archivos o de todas las políticas de seguridad.

Comprobado posteriormente en PASO 14: config.php con permiso 600 y acceso/agenda funcionales; disponibilidad de una copia de archivos y base en SuperBackup. Pendiente: permisos del resto de archivos y carpetas privadas; frecuencia y retención de copias; recuperación completa de archivos y aplicación en un entorno separado (la base de datos ya se restauró localmente); versión exacta de MySQL y revisión menor de PHP; auditoría de TLS/cabeceras y controles de seguridad en producción más allá de los recorridos comunicados; revisión ampliada de dispositivos, navegadores y accesibilidad. No se dan por verificadas estas medidas pendientes.

El retraso de verificación no establece un plazo garantizado ni identifica como responsable a Gmail, Webempresa o el buzón receptor. No se han guardado correos de usuarios, enlaces de verificación, credenciales ni datos de reservas en este registro.

Guía para futuras operaciones: MANTENIMIENTO.md. Las secciones de instalación siguientes son instrucciones de referencia para instalaciones nuevas, no acciones pendientes sobre esta instalación ya validada.

## Seguimiento del PASO 14 — 7 de octubre de 2026

Comprobaciones manuales comunicadas por el usuario; se registran sin acceder al servidor ni examinar las copias:

- El permiso de `/home/delanada/reservas-salas-private/config.php` se cambió de 644 a 600. Después del cambio, el inicio de sesión y la consulta de la agenda funcionaron correctamente. No se ha revisado por ello el resto de permisos de archivos o carpetas privadas.
- En SuperBackup se comprobó que la copia del 7 de octubre de 2026 a las 02:21 incluye la base `delanada_reservas_sala`; `/home/delanada/reservas-salas-private` con app, vendor, config.php y storage; y `/home/delanada/public_html/reservasalas.metavisuals.es`, incluidos recursos públicos y .htaccess. Se ha comprobado la disponibilidad y los elementos mostrados por SuperBackup, no su integridad mediante restauración.
- El usuario descargó en su ordenador `2026-10-07-delanada_reservas_sala.tar.gz`. En ese momento no se había inspeccionado el contenido del archivo descargado; posteriormente se comprobó mediante la restauración local descrita a continuación. La descarga registrada corresponde a ese archivo de base de datos; no acredita la descarga local de las carpetas públicas y privadas.

Estado actualizado: la copia de base de datos se ha leído e importado correctamente en una base local aislada; resultados y limpieza en la sección de recuperación siguiente. Pendiente: recuperar la aplicación completa con sus archivos y base compatible en un entorno separado y protegido, sin afectar a producción ni enviar correos reales. Frecuencia, retención y permisos del resto de archivos siguen pendientes de revisión; la existencia de esta copia no los acredita.

Las copias pueden contener credenciales y datos personales: mantenerlas fuera de Git y fuera de la carpeta pública. En este registro se incluye únicamente el nombre del archivo, nunca su contenido. No se han realizado cambios en el servidor, restauraciones ni descargas adicionales para redactar esta actualización.

## Recuperación local de la base de datos — PASO 14, 7 de octubre de 2026

Prueba realizada con `2026-10-07-delanada_reservas_sala.tar.gz`, conservando intacta la copia original descargada. El usuario había inspeccionado manualmente el SQL; en esta prueba se comprobó además la lectura del gzip/tar y la importación efectiva del único SQL regular, delanada_reservas_sala.sql. No se incluyen su contenido ni datos privados en la documentación.

Se extrajo en una carpeta temporal fuera de OneDrive y del repositorio. Se utilizó MariaDB portable 11.4.9 ya disponible, en una instancia nueva con datos propios y puerto temporal, limitada a 127.0.0.1. No se instalaron herramientas ni se cambiaron servicios. La base de restauración fue nueva e independiente. No se cargó configuración de la aplicación ni se usó la base local habitual o producción. Se omitió únicamente la instrucción USE de la base de origen para importar en el nombre temporal; el esquema y los datos se importaron sin modificarlos. El usuario de importación quedó limitado a esa base, sin privilegios globales o de archivos. El programador de eventos estaba desactivado y no se inició la aplicación ni se enviaron correos.

| Tabla recuperada | Filas |
|---|---:|
| users | 1 |
| rooms | 2 |
| reservations | 1 |
| email_tokens | 0 |
| rate_limits | 6 |

Resultado: cinco tablas InnoDB recuperadas; cantidades coincidentes con las filas de los INSERT del SQL; tres claves foráneas previstas (tokens a usuarios y reservas a usuarios/salas), sin registros huérfanos. La restricción CHECK de horarios está presente y CHECK TABLE devolvió OK para las cinco tablas. No se mostraron correos, hashes de contraseña, tokens ni conceptos.

Limpieza completada y comprobada: eliminación de la base creada, parada de la instancia temporal y eliminación de toda su carpeta temporal, incluido el SQL extraído y los datos del motor. El SHA-256 de la copia original coincide antes y después; no se publicó su contenido. Los elementos ajenos al proyecto se conservaron. No hubo commit, push ni cambios en el servidor.

Alcance: recuperabilidad de esta copia de base de datos comprobada en MariaDB 11.4.9 local. No equivale a restaurar la aplicación completa ni a probar el mismo motor/versión del hosting. Sigue pendiente ensayar en un entorno separado y protegido la recuperación conjunta de archivos públicos y privados, dependencias, configuración, storage y .htaccess, con base compatible y pruebas funcionales, sin afectar a producción ni enviar correos. Tampoco acredita la frecuencia/retención de copias, todas las políticas del hosting ni la integridad de copias de archivos aún no restauradas.
