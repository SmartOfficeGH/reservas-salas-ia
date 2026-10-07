# Aplicación Reserva Salas · Mantenimiento breve

Guía de referencia para operaciones futuras autorizadas. El usuario ha comprobado la disponibilidad de una copia en SuperBackup y descargado el archivo de base de datos indicado en el seguimiento del PASO 14. La copia de base de datos ya se ha leído y restaurado en local con limpieza posterior; la recuperación completa de la aplicación sigue pendiente. El agente no ha realizado operaciones en el servidor.

## Copias

Conservar una copia de los archivos públicos y privados, incluida la configuración real, storage y el .htaccess específico del hosting, y una exportación completa de la base de datos. Guardarlas fuera de public_html y fuera de Git, con acceso restringido: contienen secretos y datos personales. Registrar fecha, versión del código y versión del esquema. Definir responsable, frecuencia y retención; hacer una copia adicional antes de cada actualización. Comprobar que los archivos se pueden leer y la exportación está completa. Eso no sustituye una prueba de restauración en un entorno aislado y protegido.

## Actualizar

1. Revisar el paquete y su lista exacta de archivos, requisitos y posibles migraciones. No importar el esquema inicial sobre una base existente.
2. Guardar la copia previa de archivos y base. Si la actualización afecta al esquema o requiere interrupción, acordar una ventana sin escrituras para evitar reservas durante el cambio.
3. Sustituir solo los archivos indicados. Conservar config.php y storage; no copiar la configuración de ejemplo encima de la real. Mantener .htaccess del hosting e integrar únicamente cambios necesarios, conservando sus reglas de seguridad y selección de PHP.
4. Comprobar en wePanel que el subdominio mantiene PHP 8.4 — Personalizado. Revisar las URLs CSS/JavaScript con ?v= y su carga correcta; el hash debe cambiar cuando cambia el contenido del recurso.
5. Revisar acceso, agenda conjunta, colores, carriles, filtros, vista pública e iframe. Comprobar creación, persistencia y cancelación con una prueba acordada. Registro y recuperación implican correos reales: realizarlos solo cuando estén expresamente autorizados. Registrar resultado y versión instalada.

## Recuperar la versión anterior

Detener la actualización y evitar nuevas escrituras cuando la recuperación lo requiera. Si solo cambió código compatible con el esquema, restituir los archivos de la versión anterior desde la copia o paquete conocido; conservar config.php y storage actuales. Revisar .htaccess y PHP 8.4 y repetir las comprobaciones funcionales y de recursos.

Si cambió el esquema, revisar primero la compatibilidad y el procedimiento específico de reversión. No restaurar automáticamente una base antigua: puede perder reservas y usuarios creados después de la copia. Antes de cualquier restauración, guardar también el estado actual y acordar cómo preservar o reconciliar los cambios posteriores. Restaurar archivos y base compatibles mediante el procedimiento autorizado y comprobarlos. La cuenta SQL de la aplicación tiene únicamente SELECT, INSERT, UPDATE y DELETE; importaciones o cambios de esquema requieren una cuenta administrativa autorizada.

La restauración local de la base de datos está comprobada en el registro siguiente. Pendiente: ensayar y documentar en un entorno aislado la recuperación completa de la aplicación antes de considerarla comprobada. No introducir copias, credenciales, enlaces de correo ni datos de reservas en el repositorio.

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
