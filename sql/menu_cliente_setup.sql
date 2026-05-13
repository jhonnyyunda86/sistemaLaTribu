-- ══════════════════════════════════════════════════════════════════
-- MENÚ CLIENTE — La Tribu
-- Ejecutar en phpMyAdmin → bdrestaurante → SQL
-- ══════════════════════════════════════════════════════════════════

USE bdrestaurante;

-- ── 1. Agregar columna imagen a producto (URL o ruta) ─────────────
ALTER TABLE producto
    ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL AFTER descripcion;

-- ── 2. Agregar más categorías ─────────────────────────────────────
INSERT IGNORE INTO categoria_producto (nombre_categoria) VALUES
    ('Postres'),
    ('Combos'),
    ('Entradas');

-- ── 3. Asignar imágenes a productos existentes ────────────────────
UPDATE producto SET imagen = 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80' WHERE nombre = 'Hamburguesa Tribu';
UPDATE producto SET imagen = 'https://images.unsplash.com/photo-1630384060421-cb20d0e0649d?w=400&q=80' WHERE nombre = 'Salchipapa Especial';
UPDATE producto SET imagen = 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=400&q=80' WHERE nombre = 'Limonada Natural';
UPDATE producto SET imagen = 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&q=80' WHERE nombre = 'Costillas BBQ';
