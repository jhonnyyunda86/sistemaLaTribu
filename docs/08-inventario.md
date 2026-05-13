# 📦 Módulo: Inventario

**Archivos:**
- `models/Inventario.php`
- `views/dashboard/admin_inventario.php`
- `sql/inventario_setup.sql`

---

## Descripción

Controla el stock de productos en tiempo real. Se integra con el flujo de pedidos mediante triggers SQL que descuentan automáticamente el inventario al registrar un pedido.

---

## Flujo de stock

```
Crear producto (admin_menu.php)
    └── INSERT INTO inventario (stock inicial + mínimo)
    └── registrarMovimiento('entrada', stock_inicial)

Pedido confirmado (cliente_dashboard.php)
    └── INSERT INTO detalle_pedido
        └── TRIGGER trg_salida_inventario
            ├── UPDATE inventario SET cantidad_actual = GREATEST(0, cantidad_actual - cantidad)
            └── INSERT INTO movimiento_inventario (tipo='salida')

Pedido cancelado
    └── UPDATE pedido SET id_estado_pedido = 4
        └── TRIGGER trg_restaurar_inventario
            ├── UPDATE inventario (restaura stock)
            └── INSERT INTO movimiento_inventario (tipo='entrada', desc='Devolución')

Suministro manual (admin_inventario.php)
    └── UPDATE inventario SET cantidad_actual += cantidad
    └── INSERT INTO movimiento_inventario (tipo='entrada')
```

---

## Estados de stock

| Estado | Condición | Color |
|---|---|---|
| `disponible` | `cantidad_actual > cantidad_minima` | Verde |
| `bajo` | `0 < cantidad_actual <= cantidad_minima` | Ámbar |
| `agotado` | `cantidad_actual = 0` | Rojo |

Calculado en SQL:
```sql
CASE
    WHEN cantidad_actual = 0                          THEN 'agotado'
    WHEN cantidad_actual <= cantidad_minima           THEN 'bajo'
    ELSE 'disponible'
END AS estado_stock
```

---

## Dashboard de inventario (`admin_inventario.php`)

### KPI cards
```php
stats() → [
    'total'        => COUNT(*) FROM inventario,
    'agotados'     => COUNT(*) WHERE cantidad_actual = 0,
    'stock_bajo'   => COUNT(*) WHERE 0 < cantidad_actual <= cantidad_minima,
    'entradas_hoy' => SUM(cantidad) WHERE tipo='entrada' AND fecha=CURDATE()
]
```

### Tabla principal
- Barra de progreso: `pct = min(100, (cantidad_actual / (cantidad_minima * 2)) * 100)`
- Botón **Suministro** → modal con cantidad y descripción.
- Botón **Mínimo** → modal para editar el umbral de alerta.
- Botón **Agregar Producto** → modal que lista productos sin inventario.

### Historial de movimientos
- Últimos 30 movimientos con producto, tipo, cantidad y descripción.
- Badge verde para entradas, rojo para salidas.
- Cantidad con prefijo `+` o `−`.

---

## Triggers SQL

### `trg_salida_inventario`
```sql
AFTER INSERT ON detalle_pedido FOR EACH ROW
BEGIN
    -- Solo actúa si el producto tiene inventario registrado
    IF stock_actual IS NOT NULL THEN
        UPDATE inventario
        SET cantidad_actual = GREATEST(0, cantidad_actual - NEW.cantidad)
        WHERE id_producto = NEW.id_producto;

        INSERT INTO movimiento_inventario
        VALUES (NEW.id_producto, 'salida', NEW.cantidad,
                CONCAT('Salida por pedido #', NEW.id_pedido), CURDATE());
    END IF;
END
```

### `trg_restaurar_inventario`
```sql
AFTER UPDATE ON pedido FOR EACH ROW
BEGIN
    -- Solo cuando cambia a estado 4 (Cancelado)
    IF NEW.id_estado_pedido = 4 AND OLD.id_estado_pedido != 4 THEN
        UPDATE inventario i
        JOIN detalle_pedido dp ON dp.id_producto = i.id_producto
        SET i.cantidad_actual += dp.cantidad
        WHERE dp.id_pedido = NEW.id_pedido;

        INSERT INTO movimiento_inventario ...
        SELECT 'entrada', cantidad, 'Devolución por cancelación'
        FROM detalle_pedido WHERE id_pedido = NEW.id_pedido;
    END IF;
END
```

---

## Compatibilidad con BD sin migración

Los métodos `registrarMovimiento()` e `historial()` detectan dinámicamente si la columna `descripcion` existe:

```php
try {
    $this->db->query("SELECT descripcion FROM movimiento_inventario LIMIT 1");
    // usa descripcion
} catch (\PDOException $e) {
    // usa '' AS descripcion
}
```

**Para activar completamente:**
```sql
ALTER TABLE movimiento_inventario ADD COLUMN descripcion VARCHAR(200) NULL;
```

---

## Integración con panel del mesero

`mesero_stock.php` usa `Producto::obtenerMenuCliente()` que ya incluye el JOIN con inventario. El mesero tiene **solo lectura** — no puede modificar stock.
