# Aplicación Reserva Salas · Mantenimiento breve

Guía de referencia para operaciones futuras autorizadas. No se han realizado copias ni restauraciones para redactarla. Su existencia y la recuperación real siguen pendientes de comprobar.

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

Pendiente: ensayar y documentar la restauración en un entorno aislado antes de considerarla comprobada. No introducir copias, credenciales, enlaces de correo ni datos de reservas en el repositorio.
