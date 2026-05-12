-- Ejecuta esto si ya tienes datos en la BD y no quieres perderlos
ALTER TABLE usuario ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
