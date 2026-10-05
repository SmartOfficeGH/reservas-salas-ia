-- SOLO bases existentes creadas antes de la retirada de la sala pequeña.
-- Ejecutar una sola vez. No aplicar tras importar el 001_initial.sql actualizado.
-- ALTER TABLE realiza commit implícito en MySQL: hacer copia de seguridad antes.
ALTER TABLE rooms ADD COLUMN is_reservable TINYINT UNSIGNED NOT NULL DEFAULT 1;
UPDATE rooms SET is_reservable=0 WHERE id=2 AND name='SALA PEQUEÑA INNOVACIÓN';
-- Se conserva la fila de sala y TODAS sus reservas; no hay DELETE ni cambio de propietario.
