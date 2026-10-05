# Aplicación Reserva Salas · Instalación

La aplicación funcional está en `public/` y `private/`. Los archivos `index.html`, `prototipo.js` y `estilos.css` de la raíz conservan la demostración anterior: no deben subirse al sitio real. No se ha desplegado ni enviado ningún correo real.

## Parámetros de despliegue y estado de comprobación

| Parámetro | Valor previsto | Estado |
| --- | --- | --- |
| URL | https://reservasalas.metavisuals.es | Aprobada; comprobar DNS y certificado HTTPS |
| Carpeta pública | /home/delanada/public_html/reservasalas.metavisuals.es | Aprobada; verificar raíz del subdominio |
| Carpeta privada | /home/delanada/reservas-salas-private | Propuesta; confirmar que PHP puede leerla con open_basedir |
| Base | delanada_reservas_sala | Aprobada; crear/asignar en el panel |
| Usuario MySQL | delanada_reservas_app | Aprobado; asignar permisos a esa base |
| Host MySQL | localhost | Confirmado por soporte para PHP; conexión real pendiente |
| Puerto MySQL | 3306 | Confirmado por soporte; conexión real pendiente |
| Servidor SQL | MySQL con InnoDB, utf8mb4 y transacciones | Confirmar versión exacta y probar el esquema |
| PHP | 8.4, revisión de seguridad más reciente disponible | Activar para este subdominio |
| SMTP | smtp.gmail.com, puerto 587, STARTTLS | Salida permitida por soporte; autenticación, envío y entrega pendientes |
| Usuario y remitente SMTP | smartofficepalma@gmail.com | Aprobados; comprobar disponibilidad de contraseña de aplicación en Google |
| Zona horaria | Europe/Madrid | Aplicada; tokens y fechas de auditoría en UTC |

No compartas credenciales por chat ni las guardes en Git. La contraseña MySQL y la contraseña de aplicación de Gmail se introducen directamente en el servidor en `config.php`. La cuenta de Google necesita verificación en dos pasos y la opción de contraseñas de aplicación habilitada; si Google no la ofrece, hay que resolverlo antes de desplegar. No usar la contraseña habitual de Gmail ni desactivar la comprobación TLS.

## Preparar dependencias y paquete

Requisitos: PHP 8.4 con PDO, pdo_mysql, mbstring y OpenSSL; Composer 2 para preparar dependencias. En producción no hacen falta Node, Python ni Composer si se suben las dependencias incluidas en el paquete. SMTP utiliza PHPMailer, fijado por `composer.lock`. No hay tareas programadas ni servicios residentes.

Desde la carpeta del proyecto:

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
powershell -ExecutionPolicy Bypass -File scripts/package.ps1
```

Si usas las herramientas locales preparadas aquí, sustituye `composer` por `.tools/php/php.exe .tools/composer.phar`.

El ZIP de `dist/` contiene únicamente `public/`, `reservas-salas-private/`, el esquema SQL, las instrucciones y los manifiestos de dependencias. Excluye las credenciales, la base de pruebas, los enlaces de prueba, herramientas, pruebas y el prototipo. El paquete está excluido de Git.

## Instalar en Webempresa (pendiente; no realizado)

1. Confirmar los parámetros de la tabla anterior. Configurar el subdominio y su certificado HTTPS, y seleccionar PHP 8.4. [Webempresa documenta la disponibilidad de PHP 8.4](https://guias.webempresa.com/preguntas-frecuentes/versiones-php-disponibles/).
2. Descomprimir el ZIP fuera de la carpeta pública. Copiar **el contenido** de `public/`, incluido `.htaccess`, a `/home/delanada/public_html/reservasalas.metavisuals.es`. No copiar la raíz del proyecto ni el resto del paquete a esa carpeta.
3. Copiar `reservas-salas-private/` a `/home/delanada/reservas-salas-private`. Confirmar que PHP tiene permiso para leer esa carpeta privada. Si el hosting exige otra ruta, ajustar únicamente la ruta fija `$private` de `public/index.php` para que apunte fuera de `public_html`.
4. Crear o seleccionar `delanada_reservas_sala`, asignar `delanada_reservas_app` y usar phpMyAdmin para importar `database/001_initial.sql` en una **base vacía**. El esquema crea usuarios, tokens, salas, reservas y límites de intentos con InnoDB. Carga solo las dos salas reservables, sin cuentas o reservas ficticias. No reimportarlo sobre una instalación existente.
5. Copiar `config.example.php` como `config.php` dentro de la carpeta privada. Introducir ahí las credenciales y los parámetros confirmados. Mantener `environment = production`, URL HTTPS y `secure_cookies = true`. Permisos recomendados: archivo de configuración 600 y carpetas privadas 700, adaptados al usuario de PHP del hosting.
6. Asegurar que PHP puede crear y escribir `storage/sessions/` dentro de la carpeta privada (la aplicación la crea con permisos 700). El usuario SQL necesita SELECT, INSERT, UPDATE y DELETE; el esquema puede importarlo el administrador para no conceder CREATE a la aplicación. No habilitar display_errors en producción.
7. Revisar el listado de comprobaciones de hosting en `PRUEBAS.md`. Solo entonces habilitar el uso real. Esta fase sí requiere correos reales y autorización para realizar las pruebas.

## Actualizar una base existente

No reimportar `001_initial.sql` sobre una instalación existente. Hacer copia de seguridad, poner el sitio fuera de uso durante la actualización y ejecutar **una sola vez** `database/002_retire_small_room.sql` con un usuario que pueda alterar tablas. Si la columna `rooms.is_reservable` ya existe, no repetir el ALTER; comprobar el estado de la sala retirada. La migración añade el indicador y desactiva únicamente la sala pequeña (id 2); no elimina ni cambia reservas, usuarios u otras salas. ALTER TABLE tiene commit implícito en MySQL.

Después actualizar los archivos públicos y privados desde el paquete. No sustituir `config.php` ni `storage/`. Las instalaciones nuevas importan únicamente el esquema 001 actualizado; no deben ejecutar la migración 002. Las reservas antiguas de la sala retirada siguen en «Mis reservas» para su propietario, con las funciones existentes de consulta y cancelación; no vuelve a aparecer como opción reservable.

## Revisar las dos salas y las fotos

Recargar http://127.0.0.1:8089/?page=agenda con Ctrl+F5 después de iniciar sesión. La base local ya está migrada. Deben verse dos fichas y dos opciones de sala: SALA GRANDE INNOVACIÓN y SALA OTAE. Sobre la agenda, probar «Anterior» y «Siguiente» con clic o teclado (Tab y Enter); solo cambia la foto cuando se pulsa un control. Revisar nombre, edificio y adaptación a móvil. «Ver ubicación» abre el enlace aprobado en una pestaña nueva.

Las fotos originales son JPEG: `imagenes-salas/Sala Grande Innovacion.jpeg` (2000×740) e `imagenes-salas/Sala OTAE.jpg` (1280×960). El paquete incluye sus copias sin deformar en `public/images/`. Se muestran a todo el ancho con recorte proporcionado usando object-fit: cover; no se necesita un servicio externo para el carrusel.
## Revisar en local sin enviar correos

Se ha preparado una configuración privada de pruebas, PHP portable y una base temporal MariaDB en `.tools/` y `.test-data/`, excluidos de Git. Todo está en esta carpeta. La aplicación puede revisarse con el servidor local en http://127.0.0.1:8089/ mientras esos procesos estén en marcha.

Para reiniciarlos en Windows, desde la raíz del proyecto:

```powershell
# Terminal 1: solo base temporal local
& .tools/mariadb/mariadb-11.4.9-winx64/bin/mariadbd.exe --no-defaults "--datadir=$((Get-Location).Path)/.test-data/db" --bind-address=127.0.0.1 --port=33079 --console
# Terminal 2: servidor web local
& .tools/php/php.exe -S 127.0.0.1:8089 -t public
```

Si los puertos ya están en uso por estos procesos, no abrir una segunda instancia. Para apagar una instancia iniciada en terminal, usar Ctrl+C. La configuración local debe tener `environment = local`, `app_url = http://127.0.0.1:8089`, cookies sin Secure solo en local y una base cuyo nombre termine en `_test`. No usar credenciales ni datos de producción.

En local **nunca se contacta con SMTP**. Al registrar una dirección ficticia `prueba@palma.es`, el correo se captura en `private/storage/test-mail/` como JSON. Para completar el recorrido manual, abrir en el navegador local `http://127.0.0.1:8089/?page=verify&token=VALOR_DEL_JSON`, y pulsar «Confirmar mi correo». El mismo procedimiento sirve para recuperación usando `page=reset`. No compartir estos enlaces. El buzón real no recibe nada.

Las pruebas HTTP crean cuentas ficticias en la base de pruebas; el paquete nunca incluye esa base. El prototipo estático anterior sigue disponible abriendo `index.html`, pero no representa las funciones de la aplicación PHP.

## Comportamiento y límites

- Verificación: 24 horas; recuperación: 30 minutos. Solo se conserva el hash del token en SQL. Un nuevo enlace invalida el anterior del mismo tipo. Los enlaces requieren confirmación mediante formulario para que un lector automático del correo no los consuma por visitar la URL.
- Contraseña: mínimo 12 caracteres, máximo 72 bytes; Argon2id si está disponible, o el algoritmo seguro predeterminado de PHP. Una recuperación invalida las sesiones anteriores. [API oficial de contraseñas PHP](https://www.php.net/passwords).
- Sesiones: cookies HttpOnly, Secure en producción, SameSite=Lax; renovación de identificador al acceder y caducidad por 30 minutos de inactividad u 8 horas de duración. HTTPS obligatorio.
- Conceptos visibles solo a usuarios autenticados y verificados. Cancelación únicamente propia; no hay modificación, asistentes, administración, mantenimiento, recurrencia, calendario ni IA.
- Horario lunes–viernes 07:00–16:00; se admiten reservas consecutivas y del mismo día, pero el inicio no puede estar en el pasado. No se excluyen festivos ni se añaden límites de antelación máxima o duración fuera de lo aprobado.
- Las reservas sobreviven a recargas y se comparten entre sesiones. La agenda muestra los datos al consultar o recargar; no hay actualización en tiempo real. El bloqueo por sala y la lectura con FOR UPDATE dentro de la transacción impiden que dos solicitudes guarden un solapamiento, incluso si la agenda vista estaba desactualizada. [Bloqueos InnoDB](https://dev.mysql.com/doc/refman/8.0/en/select.html).
- Límites de intentos: 20 por acción y dirección IP en 15 minutos; 5 solicitudes de registro/verificación/recuperación por correo en 15 minutos. No se muestra si una dirección existe en las solicitudes por correo.
- La posesión del buzón palma.es sustituye provisionalmente a Entra ID; no conecta con el directorio ni comprueba altas o bajas de personal.

Fuentes: [Gmail y contraseñas de aplicación](https://support.google.com/mail/answer/185833), [sesiones PHP](https://www.php.net/manual/en/session.security.php).

## Recorrido desde «Ocupación de salas»

1. Iniciar sesión y recargar la agenda local con Ctrl+F5. Seleccionar un día laborable futuro o el día actual con horas aún disponibles y pulsar «Consultar fecha».
2. Revisar la única agenda de 07:00 a 16:00, con franjas de 30 minutos y las dos columnas de salas. Los bloques «Ocupado» incluyen horas exactas y concepto; sus límites no se redondean a la franja. Las fichas informativas y mapas siguen encima, y el carrusel mantiene el diseño actualizado.
3. Pulsar «＋ Libre» con el ratón o mediante Tab y Enter/espacio. Se rellena el formulario de la misma página y se enfoca el concepto. No se guarda ninguna reserva en este paso.
4. Si caben 30 minutos seguidos, se propone el fin; si no, queda vacío para introducir una duración que quepa en el hueco. Se pueden ajustar las horas antes de pulsar «Confirmar reserva».
5. Confirmar, consultar la reserva en la agenda y en «Mis reservas», y cancelar una reserva propia si se desea. Si otra persona ocupó el hueco entretanto, el servidor rechazará el solapamiento.

Los huecos pasados y días no laborables aparecen como «No reservable» y no abren el formulario desde la agenda. Una franja que pasa mientras la página está abierta también se rechaza al pulsarla. En móvil estrecho, desplazar horizontalmente la agenda para ver ambas columnas; los elementos enfocados con Tab permiten recorrerlas. En reservas muy cortas, enfocar el bloque o pasar el cursor para desplegar el texto completo.

Este cambio no requiere otra migración SQL; debe haberse aplicado la migración 002 de retirada de sala si la base es anterior. No cambia credenciales, datos ni validaciones de reserva. Los estilos de posición de los bloques utilizan un nonce aleatorio en la política de seguridad, sin habilitar estilos arbitrarios.
## Calendario Día · Semana · Mes y distribución aprobada

La página conserva el carrusel y sigue este orden: fichas de las salas, formulario Nueva reserva siempre visible, calendario Ocupación de salas y normativa. Se puede rellenar el formulario directamente sin pasar por el calendario.

- Semana es la vista inicial si no se indica otra vista, con la semana de la fecha actual en Europe/Madrid. Día muestra: ambas salas en columnas, 07:00–16:00 y franjas de 30 minutos.
- Semana muestra lunes a viernes con un filtro de sala. Los huecos libres preparan la sala, fecha y hora en el formulario superior.
- Mes presenta un resumen por día (número de reservas y minutos ocupados) de la sala filtrada. Pulsar una fecha abre Día para elegir una hora; no hay franjas dentro de las casillas mensuales.
- Anterior, Siguiente y Hoy conservan la vista y la sala elegida. La fecha y el filtro están en la URL y los controles. Las fechas de fin de mes se ajustan al último día válido al avanzar o retroceder; por ejemplo, 31 de enero pasa a 28/29 de febrero.
- Al cambiar el filtro, el formulario toma esa sala como selección inicial. Al confirmar una reserva, el calendario conserva su vista y se sitúa en la fecha y sala reservadas.

Para revisar: recargar con Ctrl+F5, comprobar el orden, alternar vistas, filtrar ambas salas, avanzar de diciembre a enero y de enero a febrero, usar Hoy y abrir un día desde Mes. Pulsar un hueco en Día y otro en Semana: el formulario debe quedar preparado, enfocado y permitir ajustar los datos antes de confirmar. En móvil, los periodos anchos permiten desplazamiento horizontal con foco de teclado; los controles y el formulario se adaptan al ancho.

No se necesita migración SQL adicional para estas vistas. No se modificaron las restricciones del servidor ni la protección ante reservas simultáneas.
## Identidad corporativa

Los recursos necesarios están en `public/brand/` y el tema en `public/corporativo.css`. Subir toda la carpeta pública, incluidos logo, fuentes y avisos OFL. No se necesita la carpeta original `RE__Estilo_corporativo_`, excluida de Git y del paquete. Véase `IDENTIDAD-CORPORATIVA.md` para procedencia, adaptación y limitaciones. No hay cambios de esquema ni de datos por este ajuste.

Webempresa ha confirmado MySQL desde PHP en `localhost:3306` y salida SMTP a `smtp.gmail.com:587` con STARTTLS. Esto confirma los parámetros y la conectividad permitida por soporte, pero no una conexión real realizada: durante el despliegue aún hay que comprobar autenticación MySQL, permisos, TLS, autenticación Gmail y entrega al buzón. Las dos contraseñas permanecen vacías en el ejemplo y se introducirán únicamente en el servidor.

Revisión local: abrir http://127.0.0.1:8089/ y recargar con Ctrl+F5. Revisar acceso, registro, verificación, recuperación, agenda y Mis reservas; en la agenda comprobar el carrusel, fichas, formulario anterior al calendario y las tres vistas.

## Fichas y vista inicial actualizadas

Las fichas incorporan iconos SVG locales de ubicación, personas e información, con trazo común y azul corporativo. Son decorativos (texto alternativo vacío y aria-hidden); se conservan edificio, aforo, descripción y mapas. No hay dependencias externas.

Al abrir la agenda sin `view`, se muestra Semana. Sin `day`, se usa la fecha actual de Europe/Madrid. Las vistas explícitas Día, Semana y Mes se respetan; Mes sigue abriendo Día al elegir una fecha. Se mantienen sala filtrada, navegación y preparación del formulario desde huecos libres.

## Consulta pública e inserción

Subir también `public/ocupacion.php` y los demás recursos incluidos en el paquete; no requiere migración ni credenciales nuevas. La entrada pública no inicia sesión, acepta únicamente GET/HEAD y consulta solo sala/fecha/inicio/fin mediante consultas preparadas. No carga conceptos ni datos de usuarios o identificadores de reservas.

URL de producción: `https://reservasalas.metavisuals.es/ocupacion.php`. Semana y periodo actual de Europe/Madrid por defecto. Parámetros opcionales: `view=day|week|month`, `room=1|3` (en Día también `all`) y `day=AAAA-MM-DD`. Día sin sala indica ambas; al compartir se conserva el filtro de la aplicación. Sin `day`, Actualizar vuelve al periodo actual. Los enlaces generados usan `app_url` de la configuración, nunca el Host de la petición ni una dirección local fija.

Para compartir, iniciar sesión y bajar al final de Ocupación de salas a «Compartir Ocupación Salas». Pulsar «Compartir» y elegir «Copiar URL» o «Copiar iframe». El menú se cierra y confirma «URL copiada» o «Código iframe copiado». No hay campos de código visibles permanentemente; solo si falla el portapapeles se muestra el contenido seleccionado para copiarlo manualmente. El menú admite flechas, Inicio/Fin, Enter/Espacio y cierre con Escape, Tab o clic fuera. El iframe tiene título accesible y ancho 100%; su altura de 1000 px se puede ajustar en la web receptora. Las vistas largas tienen desplazamiento dentro del iframe y Semana/Mes permiten desplazamiento horizontal en móviles. No hay actualización automática: Actualizar recarga los datos actuales y las respuestas llevan `Cache-Control: no-store`.

Seguridad en hosting: la aplicación envía `frame-ancestors *` solamente en `ocupacion.php`. Las demás pantallas mantienen `frame-ancestors 'none'` y `X-Frame-Options: DENY`. Si Webempresa añade cabeceras globales de inserción o caché, comprobar y excluir únicamente esta entrada pública donde corresponda; no relajar las cabeceras de las pantallas autenticadas. La web receptora puede necesitar permitir el dominio en su propia política `frame-src`. Usar HTTPS en ambas webs para evitar contenido mixto.

Revisión local: abrir http://127.0.0.1:8089/ocupacion.php sin sesión; cambiar Día/Semana/Mes, fechas y sala y pulsar Actualizar. Los huecos no preparan ni crean reservas. Desde la aplicación autenticada, probar los dos botones de copia y abrir el enlace en una ventana sin sesión. No se ha realizado despliegue ni enviado correo real.
