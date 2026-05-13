-- ══════════════════════════════════════════════════════════════════
-- MÓDULO INVENTARIO — La Tribu
-- Ejecutar en phpMyAdmin → bdrestaurante → SQL
-- ══════════════════════════════════════════════════════════════════

USE bdrestaurante;

-- ── 1. Agregar columna descripcion a movimiento_inventario ─────────
ALTER TABLE movimiento_inventario
    ADD COLUMN IF NOT EXISTS descripcion VARCHAR(200) NULL AFTER cantidad;

-- ── 2. Asegurar UNIQUE en inventario (1 registro por producto) ─────
ALTER TABLE inventario
    ADD CONSTRAINT IF NOT EXISTS uq_inventario_producto UNIQUE (id_producto);

-- ── 3. Inicializar inventario para productos que no lo tienen ──────
INSERT IGNORE INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion)
SELECT id_producto, 0, 5, CURDATE()
FROM producto
WHERE id_producto NOT IN (SELECT id_producto FROM inventario WHERE id_producto IS NOT NULL);

-- ── 4. TRIGGER: descontar stock al insertar detalle_pedido ─────────
DROP TRIGGER IF EXISTS trg_salida_inventario;

DELIMITER $$
CREATE TRIGGER trg_salida_inventario
AFTER INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
    DECLARE stock_actual INT DEFAULT 0;

    -- Obtener stock actual
    SELECT cantidad_actual INTO stock_actual
    FROM inventario
    WHERE id_producto = NEW.id_producto
    LIMIT 1;

    -- Solo descontar si hay inventario registrado
    IF stock_actual IS NOT NULL THEN
        -- Actualizar stock (mínimo 0)
        UPDATE inventario
        SET cantidad_actual    = GREATEST(0, cantidad_actual - NEW.cantidad),
            fecha_actualizacion = CURDATE()
        WHERE id_producto = NEW.id_producto;

        -- Registrar movimiento de salida
        INSERT INTO movimiento_inventario
            (id_producto, tipo_movimiento, cantidad, descripcion, fecha_movimiento)
        VALUES
            (NEW.id_producto, 'salida', NEW.cantidad,
             CONCAT('Salida por pedido #', NEW.id_pedido),
             CURDATE());
    END IF;
END$$
DELIMITER ;

-- ── 5. TRIGGER: restaurar stock si se cancela un pedido ───────────
DROP TRIGGER IF EXISTS trg_restaurar_inventario;

DELIMITER $$
CREATE TRIGGER trg_restaurar_inventario
AFTER UPDATE ON pedido
FOR EACH ROW
BEGIN
    -- id_estado_pedido = 4 → Cancelado
    IF NEW.id_estado_pedido = 4 AND OLD.id_estado_pedido != 4 THEN
        -- Restaurar stock de cada producto del pedido
        UPDATE inventario i
        JOIN detalle_pedido dp ON dp.id_producto = i.id_producto
        SET i.cantidad_actual    = i.cantidad_actual + dp.cantidad,
            i.fecha_actualizacion = CURDATE()
        WHERE dp.id_pedido = NEW.id_pedido;

        -- Registrar movimientos de devolución
        INSERT INTO movimiento_inventario (id_producto, tipo_movimiento, cantidad, descripcion, fecha_movimiento)
        SELECT id_producto, 'entrada', cantidad,
               CONCAT('Devolución por cancelación pedido #', NEW.id_pedido),
               CURDATE()
        FROM detalle_pedido
        WHERE id_pedido = NEW.id_pedido;
    END IF;
END$$
DELIMITER ;

-- ── 6. Datos de prueba: stock inicial para los 4 productos ────────
UPDATE inventario SET cantidad_actual = 50, cantidad_minima = 10 WHERE id_producto = 1;
UPDATE inventario SET cantidad_actual = 40, cantidad_minima = 8  WHERE id_producto = 2;
UPDATE inventario SET cantidad_actual = 80, cantidad_minima = 15 WHERE id_producto = 3;
UPDATE inventario SET cantidad_actual = 30, cantidad_minima = 5  WHERE id_producto = 4;
