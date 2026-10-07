# Pruebas de la aplicación

Fecha: 5 de octubre de 2026. No se ha usado el hosting ni enviado correo real.

## Resultados locales

- PHP 8.4.13 portable: comprobación de sintaxis de los archivos PHP.
- 28 comprobaciones unitarias: dominio exacto y dominios rechazados, hash y verificación de contraseña, longitud de contraseña, concepto obligatorio, fechas válidas, lunes a viernes, horario, orden de horas, pasado y reserva del mismo día; escape HTML y CSRF.
- 31 comprobaciones de integración con MariaDB 11.4.9 e InnoDB: registro no verificado, rechazo de acceso/reserva sin verificación, tokens con hash, un solo uso, caducidad de verificación y recuperación, sustitución del enlace, rechazo de tokens de otro tipo o inexistentes, protección de la contraseña ante un registro duplicado, acceso, recuperación y revocación de sesiones, persistencia, agenda compartida, propiedad de cancelación, huecos consecutivos, independencia de salas y liberación tras cancelar; límite de intentos.
- Concurrencia: dos procesos PHP y dos conexiones distintas, con un bloqueo de sala retenido mientras ambos arrancan. Resultado: una reserva confirmada, una rechazada por solapamiento y exactamente una fila guardada.
- 51 comprobaciones HTTP reales al servidor local: acceso, registro, verificación, recuperación, sesiones, cierre de sesión, cabeceras de protección, CSRF, datos de salas y normativa, agenda compartida, creación, rechazo de solapamiento y horarios, consulta propia, intento de cancelar una reserva ajena y cancelación propia. Incluye caducidad por inactividad y duración máxima, renovación del identificador de sesión y rechazo de formularios anteriores a esa caducidad.
- Auditoría Composer: sin avisos de vulnerabilidades conocidos en las dependencias instaladas en el momento de la comprobación.

Los envíos se sustituyeron por funciones de captura o archivos JSON privados. Las pruebas no verifican SMTP, entrega a palma.es ni identidad de personas reales. La revisión inicial no se automatizó; la revisión corporativa posterior en Edge se describe al final de este documento.

Revisión de interfaz actualizada: «Cerrar sesión» usa texto blanco sobre azul corporativo, con contraste calculado de 7,93:1 en estado normal y 10,94:1 al pasar el cursor. Registro y recuperación muestran una indicación sin bytes. Se comprobó por HTTP local el mensaje de contraseña demasiado larga y mediante PHP que se conserva el límite de 72 bytes, con pruebas de texto ASCII y letras con tilde. Los 28 controles unitarios siguen pasando.

## Repetir pruebas

Usar únicamente una configuración `local` y una base desechable cuyo nombre termine en `_test`, con el esquema importado previamente. Nunca ejecutar estos scripts contra producción.

```powershell
& .tools/php/php.exe tests/unit.php
& .tools/php/php.exe tests/integration.php
& .tools/php/php.exe tests/concurrency.php
# Con servidor PHP local en 127.0.0.1:8089 y Python 3.9+:
python tests/test_web.py
```

El recorrido HTTP conserva sus cuentas ficticias en la base local. Las pruebas de integración y concurrencia eliminan sus propias cuentas y reservas al finalizar correctamente. Los enlaces capturados y logs de prueba están excluidos del paquete y de Git.

## Pendiente en el hosting, antes de uso real

1. Confirmar versión exacta de MySQL, tablas InnoDB, permisos y conectividad. Repetir en una **base de pruebas separada** la importación y la concurrencia. Las pruebas locales se han hecho con MariaDB, no con MySQL del hosting.
2. Confirmar PHP 8.4 actualizado y extensiones PDO MySQL, mbstring y OpenSSL. Confirmar la lectura de la carpeta privada y el almacenamiento seguro de sesiones.
3. Confirmar DNS, certificado HTTPS, redirección desde HTTP, cookie Secure/HttpOnly/SameSite y que configuración, dependencias, SQL y pruebas no son accesibles por URL.
4. Con autorización para enviar correos reales, comprobar Gmail por STARTTLS en 587 y la llegada de verificación y recuperación a un buzón palma.es. Comprobar caducidad, un solo uso y reenvío en el entorno real. No desactivar certificados TLS ni imprimir credenciales para diagnosticar.
5. Con dos cuentas de prueba verificadas, consultar conceptos compartidos y reservas propias; intentar cancelación ajena y crear simultáneamente el mismo horario desde sesiones independientes. Solo debe guardarse una reserva.
6. Comprobar las horas de Europe/Madrid, reservas del mismo día con inicio futuro, fin de semana, horarios fuera de rango y cambio horario estacional. Revisar las tres pantallas en ordenador y móvil, y navegar con teclado.
7. Comprobar recuperación, cierre de sesión, caducidad por inactividad y rechazo de una sesión anterior tras cambiar contraseña.

No se considera validado el despliegue ni la entrega de correo hasta completar esta lista en el hosting.

## Retirada de sala y carrusel

- Migración comprobada en una base local aislada con esquema anterior: conserva exactamente las reservas de las tres salas, mantiene su consulta propia y bloquea una nueva reserva de sala pequeña. La base local de revisión también se ha actualizado sin borrar sus reservas.
- Integración: únicamente ids 1 y 3 reservables y rechazo del id 2 en el servidor.
- HTTP: dos fichas y dos opciones de formulario; carrusel sobre la agenda con dos fotos, controles con nombres accesibles y los dos enlaces exactos con target=_blank y rel=noopener noreferrer. Las dos imágenes se sirven como JPEG. Una petición manipulada para reservar id 2 se rechaza.
- JavaScript: anterior/siguiente, recorrido circular, una única diapositiva visible, estado accesible y ausencia de temporizadores comprobados con DOM simulado (`node tests/carousel.cjs`).
- Ejecutar `php tests/migration.php` solo con configuración local y base _test; crea una base aislada de prueba y conserva allí el resultado para inspección.
- La conexión al navegador para revisión visual agotó su tiempo de espera. Queda revisar manualmente el tamaño y aspecto del carrusel en móvil y escritorio y abrir los enlaces de ubicación. No se ha comprobado el destino final de los enlaces acortados de Google.
## Integración visual del carrusel

Imagen a todo el ancho con object-fit: cover, altura de 260 px en escritorio y 200 px en móvil. Se revisaron las dos fotografías originales; el encuadre de OTAE se sitúa en 50% 58% para priorizar la zona de reunión. Nombre y edificio se superponen sobre degradado oscuro. Flechas laterales discretas con etiquetas accesibles, foco visible e indicador actual. Sin avance automático y con controles nativos utilizables mediante Tab, Enter y espacio.

Comprobados la sintaxis PHP, los controles JavaScript con la prueba existente y el HTML de las dos diapositivas, etiquetas y mapas. La herramienta del navegador falló al conectar; sigue pendiente la revisión visual manual del recorte a distintos anchos. Las funciones de reserva, mapas, fotografías originales y lógica de navegación no se modificaron.
## Agenda «Ocupación de salas»

- 47 comprobaciones PHP de cálculo y representación: 18 franjas, límite de cierre, huecos antes y después de reservas 09:10–09:45, propuesta de 30 minutos solo si hay espacio continuo, ausencia de solapamientos en huecos, pasado, fines de semana y precisión de posiciones. Comprobado un bloque de 35 minutos con posición y altura proporcionales, sin redondear a 30 o 60 minutos.
- Prueba JavaScript con DOM simulado: sala, fecha, inicio y fin se trasladan al formulario; se conserva el concepto escrito, se desplaza y enfoca el formulario, no se hace ninguna petición de guardado, se deja el fin vacío en huecos cortos, se rechazan franjas deshabilitadas y horas que ya pasaron.
- HTTP: dos columnas en una única agenda y reserva real representada como bloque; la antigua lista duplicada no se renderiza. Los 44 controles HTTP, 31 de integración, 28 unitarios, carrusel y concurrencia siguen pasando.
- El servidor conserva todas sus validaciones. No hubo cambios de esquema ni envíos reales de correo. La revisión visual manual en ordenador/móvil sigue pendiente; las comprobaciones automáticas cubren HTML, geometría y comportamiento.

Para repetir las nuevas pruebas: `php tests/occupancy.php` y `node tests/booking-slots.cjs` desde la raíz del proyecto.
## Vistas Día, Semana y Mes

19 comprobaciones en `tests/calendar.php`: Semana inicial, selecciones explícitas de Día y Mes, semana lunes–viernes, avance semanal, meses de 28/29/30/31 días, año nuevo, ajuste de fecha al cambiar de mes, filtros y estado conservados en enlaces, dos columnas diarias, cinco columnas semanales, horas exactas en la sala filtrada y resumen mensual sin franjas. Mes abre Día conservando la fecha y sala.

51 comprobaciones HTTP: orden aprobado de la página, vistas y filtros con reservas reales de prueba, resumen mensual, enlaces diarios, navegación de enero a febrero y Hoy. Se mantienen los 47 controles de ocupación, 28 unitarios, 31 de integración, controles de carrusel, traslado de huecos al formulario y concurrencia. Las pruebas usan datos ficticios y transporte de correo local; no envían correos reales.

Para repetir: `php tests/calendar.php`, además de las pruebas de ocupación, huecos, HTTP y reservas documentadas. La revisión visual manual de las tres vistas en móvil y escritorio sigue pendiente.
## Revisión corporativa posterior (5 de octubre de 2026)

- Edge sin interfaz visible: 47 comprobaciones a 1280 × 900 y 390 × 844, capturas de acceso, registro, recuperación, verificación, cambio de contraseña, Mis reservas y agenda Día/Semana/Mes. Revisadas visualmente las capturas de formularios, cabecera, fotos, fichas y calendario. Sin desbordamiento horizontal de página; el calendario ancho conserva desplazamiento interno. Fuentes cargadas, logo completo, una diapositiva visible y sin errores de JavaScript ni recursos bloqueados.
- Teclado: Enter cambia la foto y activa un hueco libre, prepara las horas y lleva el foco al concepto sin crear reserva. Se conservan foco visible, texto Libre/Ocupado/No reservable y franjas rayadas.
- Repetidas 51 comprobaciones HTTP, 28 unitarias, 31 de integración, 47 de ocupación y 19 de calendario; concurrencia con dos procesos: solo una reserva. Controles JavaScript de carrusel y huecos correctos. Sintaxis PHP comprobada. Correos exclusivamente capturados en local.
- Contraste calculado: azul/blanco 7,93:1, hover/blanco 10,94:1 y texto secundario/blanco 7,34:1. Gris/blanco 3,74:1 y turquesa/blanco 2,53:1, por lo que no se usan como texto pequeño sobre blanco. Esto no sustituye una auditoría completa de accesibilidad.
- Para repetir la revisión de navegador: ejecutar primero `tests/test_web.py` (crea sus cuentas sintéticas), tener Playwright disponible para Node y ejecutar `node tests/corporativo.cjs` desde la raíz. `BROWSER_EXECUTABLE` permite indicar el ejecutable; por defecto usa Edge en Windows. Capturas en `.test-data/brand/`, excluidas de Git y del paquete. La prueba exige configuración local y base de pruebas. No crea reservas ni verifica cuentas manualmente.
- Pendiente: revisión en teléfonos reales, otros navegadores y lector de pantalla; aprobación final de identidad visual por el Ayuntamiento. Las referencias previas a revisión visual pendiente quedan sustituidas por esta revisión de Edge, con estos límites.
- Soporte permite MySQL localhost:3306 y SMTP smtp.gmail.com:587 STARTTLS. No se ha probado la conexión real ni envío/entrega en el hosting; siguen pendientes durante el despliegue autorizado.

## Fichas y vista inicial actualizadas

Las fichas incorporan iconos SVG locales de ubicación, personas e información, con trazo común y azul corporativo. Son decorativos (texto alternativo vacío y aria-hidden); se conservan edificio, aforo, descripción y mapas. No hay dependencias externas.

Al abrir la agenda sin `view`, se muestra Semana. Sin `day`, se usa la fecha actual de Europe/Madrid. Las vistas explícitas Día, Semana y Mes se respetan; Mes sigue abriendo Día al elegir una fecha. Se mantienen sala filtrada, navegación y preparación del formulario desde huecos libres.

Verificación de esta actualización: 21 comprobaciones de calendario, 47 de ocupación, 56 del recorrido HTTP y 51 de navegador Edge a 1280 × 900 y 390 × 844. Iconos cargados localmente, decorativos y a 18 px; capturas de ambas fichas revisadas sin deformaciones ni problemas de alineación. Semana inicial, vistas explícitas, filtros, navegación de periodos, Mes a Día y teclado hacia formulario comprobados. Sintaxis PHP correcta. Sigue pendiente la revisión en dispositivos físicos y otros navegadores. No se han enviado correos reales.

## Nombre visible actualizado

«Aplicación Reserva Salas» en cabecera, pestañas, pie, remitente, asunto y cuerpo de verificación/recuperación. 57 comprobaciones HTTP y 83 de navegador correctas; título revisado visualmente a 390 px, sin desbordamiento. Los mensajes locales capturados usan el mismo contenido que SMTP, sin enviar correos reales. Se conservan los nombres técnicos de carpeta y repositorio.

## Consulta pública y compartir

- `tests/public.php`: 40 comprobaciones de acceso anónimo, proyección de base de datos limitada a sala/fecha/inicio/fin, ausencia de conceptos/correos/identificadores de reservas en HTML y atributos, salas activas, horarios exactos, estados, caché desactivada y ausencia de cookies de sesión, métodos POST rechazados, GET con acciones sin efecto, parámetros malformados, filtros Día/Semana/Mes, fecha actual Madrid y enlaces generados con URL de producción. Comprobada la actualización tras cancelar una reserva sintética.
- Con `--browser` se añade una comprobación del resultado de la revisión de navegador: total PHP 41. La reserva sintética pertenece a una cuenta verificada mediante el flujo de token de prueba, se usa solo en la base local y se elimina junto con la cuenta en finally incluso si falla la revisión.
- `tests/public-browser.cjs`: 48 comprobaciones con Edge a 1280 y 390 px. Revisadas capturas de Día/Semana/Mes y un iframe real con origen distinto (servidor HTTP local temporal). Sin desbordamiento de página; el desplazamiento ancho queda dentro del calendario. Bloque real de 09:10–09:45 visible con duración proporcional y sin concepto. Huecos sin acciones; Actualizar conserva estado; filtros, periodo anterior/siguiente, Hoy y Mes a Día funcionan.
- Comprobadas copia de enlace e iframe con título accesible, conservación de vista/sala/fecha explícita, omisión de fecha implícita y alternativa manual cuando el portapapeles rechaza la copia. El iframe público carga; el navegador bloquea de verdad el iframe de la agenda autenticada por su política de inserción.
- Repetidas las 57 comprobaciones HTTP autenticadas, 28 unitarias, 31 de integración, protección de solapamientos simultáneos, 21 de calendario y 47 de ocupación. Sin SMTP ni acceso al hosting. No hay migración de base de datos por esta ampliación.

Para repetir, arrancar la aplicación local y ejecutar primero `tests/test_web.py`, después `php tests/public.php`. Para incluir el navegador, tener Node/Playwright disponibles y ejecutar `php tests/public.php --browser`; `BROWSER_EXECUTABLE` permite elegir un ejecutable compatible (Edge de Windows por defecto). El servidor temporal de inserción usa un puerto libre en 127.0.0.1 y se cierra al terminar; las capturas se guardan en `.test-data/public/`, fuera de Git y del paquete.

Pendiente en hosting: comprobar cabeceras efectivas de Webempresa y de la web receptora, iframe con HTTPS real, actualización sin caché intermedia, y revisión en dispositivos físicos/otros navegadores/lector de pantalla. La consulta pública revela únicamente la ocupación aprobada; conceptos y acciones de reserva permanecen dentro del acceso autenticado.

## Menú de compartir simplificado

66 comprobaciones de navegador público/compartir correctas. Se mantiene la generación de URL e iframe; único botón visible «Compartir», título exacto y menú con dos opciones. Comprobados contenidos copiados, confirmaciones exactas, cierre tras elegir, flechas/Inicio/Fin/Enter, Escape con devolución de foco, clic fuera, Tab, y contenido manual seleccionado para URL e iframe ante rechazo del portapapeles. Una copia posterior correcta vuelve a ocultar el contenido manual. Capturas revisadas a 1280 y 390 px; sin desbordamiento de página. Los 41 controles PHP de privacidad, solo lectura, filtros e inserción siguen pasando. No se ha cambiado la vista pública ni ninguna función de reserva.

## Consulta conjunta — PASO 13 (6 de octubre de 2026)

- 29 comprobaciones de calendario: Semana y Todas las salas iniciales, filtros individuales/conjuntos en las tres vistas, navegación y URL `room=0`.
- `php tests/joint.php`: 9 controles de disponibilidad y 70 controles de navegador Edge a 1280 y 390 px. Crea una cuenta ficticia mediante registro y token local, cuatro reservas sintéticas y las elimina al terminar. Comprueba reservas 09:10–10:15 y 09:30–10:40 parcialmente coincidentes, y dos 13:00–14:00 simultáneas en salas distintas: carriles separados y posiciones reales. Comprueba colores diferentes, nombres/leyenda, dos columnas diarias, resumen mensual por sala, filtros, privacidad pública, teclado hacia formulario, sala obligatoriamente sin seleccionar en conjunto, consulta real de disponibilidad, hueco insuficiente para 30 minutos y conservación del filtro en compartir/Mes a Día. Capturas en `.test-data/joint/`, excluidas de Git y del paquete. Requiere aplicación y base local `_test`, Node y Playwright disponibles.
- Repasadas 57 comprobaciones HTTP de registro, verificación, acceso, permisos, reservas y cancelación; 28 unitarias, 31 de integración, 47 de ocupación; prueba concurrente de dos procesos confirma solo una reserva en la misma sala. Correo exclusivamente simulado, sin SMTP.
- 41 controles PHP y 66 de navegador de consulta pública/compartir: sin conceptos, usuarios ni identificadores de reservas; sin acciones; iframe público y protección de pantallas autenticadas; URL/iframe y alternativa manual. Carrusel y traslado de huecos al formulario siguen pasando.
- Repetidas también las 83 comprobaciones de identidad corporativa y pantallas de acceso en Edge.
- Revisadas capturas en escritorio y móvil: orden de página conservado, colores/leyenda legibles, formulario accesible y desplazamiento horizontal dentro del calendario, sin desbordamiento de página. La política de seguridad permite únicamente consultas de disponibilidad al propio origen en pantallas autenticadas; la vista pública mantiene connect-src none.

Pendiente de revisión: teléfonos físicos, otros navegadores y lector de pantalla; revisión local por el usuario y posterior actualización autorizada del hosting. No hay cambios de esquema ni datos de producción. Mantener PHP 8.4 y el .htaccess actual según INSTALACION.md.

## Versionado de recursos y diagnóstico de caché

- Lectura del hosting: estilos.css y corporativo.css idénticos byte a byte a los locales, con caché de un año; app.js y compartir.js también idénticos y HTTP 200. Navegador limpio muestra dos carriles por día y dos reservas reales simultáneas con colores diferentes. No se alteraron reservas, sesiones del usuario, credenciales ni servidor.
- `php tests/assets.php`: 14 controles de SHA-256 estable para contenido idéntico, cambio de URL al cambiar el contenido, rechazo de nombres fuera de la lista y archivos ausentes. Utiliza archivos temporales propios, eliminados al terminar.
- `php tests/joint.php`: 9 controles de servidor y 127 controles de navegador. Comprueba hash exacto de cada CSS/JavaScript en las dos entradas, HTTP 200 y simulación de estilos antiguos en URLs sin versión: las páginas no las solicitan. Comprueba prioridades efectivas, carriles posicionados, separación de cabeceras, geometría de reservas simultáneas/parcialmente coincidentes, colores diferentes, filtros, tres vistas, teclado y privacidad. Capturas de 1280 y 390 px revisadas visualmente, con desplazamiento hasta el día de las reservas sintéticas.
- Repetidas 41 comprobaciones públicas y 66 de navegador de privacidad/compartir/iframe; 57 del recorrido HTTP con correo simulado. Sin cambios de negocio ni SQL.
- Repetidas las 83 comprobaciones de identidad corporativa y pantallas en Edge; la prueba espera explícitamente la carga de iconos y estilos antes de medirlos.
- En el diagnóstico inicial no se reprodujo el fallo con caché limpia. La posterior validación manual en Webempresa está registrada a continuación. Sigue pendiente una revisión más amplia con dispositivos físicos, otros navegadores y lector de pantalla.

## Validación manual en producción comunicada por el usuario

Validación manual comunicada por el usuario el 6 de octubre de 2026: la corrección de caché está desplegada en Webempresa y las salas se muestran separadas, con colores distintos y sin superposición. La vista pública conserva la privacidad y no permite reservar. Esta validación en el hosting la realizó el usuario; el agente no efectuó cambios en el servidor.

El primer correo de verificación tardó aproximadamente tres minutos en llegar. El envío y la verificación mediante el enlace funcionaron. No se ha determinado la causa del retraso; esta observación no establece un tiempo de entrega garantizado ni demuestra que el retraso proceda de Gmail, Webempresa o el buzón receptor.

No se han enviado correos ni modificado el hosting para registrar este resultado. La prueba satisfactoria corresponde a ese primer recorrido de verificación; no acredita por sí sola todas las modalidades de correo o todos los buzones.

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

Pendiente de comprobar o documentar: permisos efectivos de config.php y carpetas privadas; existencia, frecuencia y retención de copias; restauración real de archivos y base de datos; versión exacta de MySQL y revisión menor de PHP; auditoría de TLS/cabeceras y controles de seguridad en producción más allá de estos recorridos; revisión ampliada de dispositivos, navegadores y accesibilidad. No se dan por verificadas estas medidas.

El retraso de verificación no establece un plazo garantizado ni identifica como responsable a Gmail, Webempresa o el buzón receptor. No se han guardado correos de usuarios, enlaces de verificación, credenciales ni datos de reservas en este registro.

Guía para futuras operaciones: MANTENIMIENTO.md. Las secciones de instalación siguientes son instrucciones de referencia para instalaciones nuevas, no acciones pendientes sobre esta instalación ya validada.
