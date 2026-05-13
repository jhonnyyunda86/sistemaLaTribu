# 📦 InventarioController

**Archivo:** `controllers/InventarioController.php`  
**Modelo:** `Inventario`  
**Acceso:** Solo Admin

---

## Propósito

Gestiona todas las operaciones manuales sobre el inventario: agregar suministros, ajustar el stock mínimo de alerta, registrar productos que aún no tienen inventario y consultar el historial de movimientos. Las operaciones automáticas (descuento por pedido, restauración por cancelación) las manejan los triggers SQL.

---

## Constructor

```php
public function __construct()
{
    $this->db       = (new Database())->conectar();
    $this->invModel = new Inventario($this->db);
}
```

---

## `stats(): array`

Retorna los 4 KPIs del dashboard de inventario.

```php
// Retorna:
[
    'total'        => COUNT(*) FROM inventario,
    'agotados'     => COUNT(*) WHERE cantidad_actual = 0,
    'stock_bajo'   => COUNT(*) WHERE 0 < cantidad_actual <= cantidad_minima,
    'entradas_hoy' => SUM(cantidad) WHERE tipo='entrada' AND fecha=CURDATE(),
]
```

---

## `obtenerTodos(): array`

Retorna todos los productos con su estado de stock calculado en SQL:

```sql
CASE
    WHEN cantidad_actual = 0                          THEN 'agotado'
    WHEN cantidad_actual <= cantidad_minima           THEN 'bajo'
    ELSE 'disponible'
END AS estado_stock
```

---

## `agregarSuministro(array $datos): array`

Aumenta el stock de un producto y registra el movimiento.

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_inventario` | int | ✅ |
| `cantidad` | int (> 0) | ✅ |
| `descripcion` | string | ❌ |

### Flujo interno (transacción en el modelo)

```
1. Validar id_inventario > 0 y cantidad > 0
2. Inventario::agregarSuministro(idInv, cantidad, descripcion)
   ├── beginTransaction()
   ├── UPDATE inventario SET cantidad_actual += cantidad
   ├── INSERT INTO movimiento_inventario (tipo='entrada')
   └── commit()
```

---

## `actualizarMinimo(array $datos): array`

Cambia el umbral de alerta de stock bajo para un producto.

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_inventario` | int | ✅ |
| `cantidad_minima` | int (>= 0) | ✅ |

> Permite `0` como valor mínimo (desactiva la alerta de stock bajo).

---

## `registrarProducto(array $datos): array`

Crea el registro de inventario para un producto que aún no lo tiene.

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_producto` | int | ✅ |
| `stock_inicial` | int | ❌ (default 0) |
| `stock_minimo` | int | ❌ (default 5) |

### Flujo interno

```
1. Validar id_producto > 0
2. SELECT COUNT(*) FROM inventario WHERE id_producto = :id
   → Si ya existe → error 'Este producto ya tiene registro en inventario.'
3. INSERT INTO inventario (id_producto, stock_inicial, stock_minimo, CURDATE())
4. Si stock_inicial > 0 → registrarMovimiento('entrada', stock_inicial, 'Stock inicial registrado')
```

---

## `historial(int $limite, ?int $idProducto): array`

Retorna los últimos N movimientos de inventario.

| Parámetro | Default | Descripción |
|---|---|---|
| `$limite` | 30 | Número máximo de registros |
| `$idProducto` | null | Si se pasa, filtra por producto |

Detecta dinámicamente si la columna `descripcion` existe en `movimiento_inventario`:
```php
try {
    $this->db->query("SELECT descripcion FROM movimiento_inventario LIMIT 1");
    $campoDesc = "m.descripcion";
} catch (\PDOException $e) {
    $campoDesc = "'' AS descripcion";  // fallback si no existe la columna
}
```

---

## `hayStock(int $idProducto, int $cantidad): bool`

Verifica si hay suficiente stock antes de procesar un pedido.

```php
// Retorna true si cantidad_actual >= $cantidad
// Retorna false si no hay inventario registrado o stock insuficiente
```

Usado internamente por `PedidoController` antes de insertar `detalle_pedido`.

---

## Relación con los triggers SQL

| Operación | Quién la ejecuta |
|---|---|
| Suministro manual | `InventarioController::agregarSuministro()` |
| Descuento por pedido | Trigger `trg_salida_inventario` (automático) |
| Restauración por cancelación | Trigger `trg_restaurar_inventario` (automático) |
| Stock inicial al crear producto | `ProductoController::crear()` |
| Stock inicial desde inventario | `InventarioController::registrarProducto()` |

---

## Ejemplo de uso

```php
require_once __DIR__ . '/../../controllers/InventarioController.php';

$ctrl = new InventarioController();

// KPIs
$stats = $ctrl->stats();

// Agregar suministro
$resultado = $ctrl->agregarSuministro([
    'id_inventario' => 3,
    'cantidad'      => 20,
    'descripcion'   => 'Compra proveedor semanal',
]);

// Historial
$movimientos = $ctrl->historial(50);

// Verificar stock antes de un pedido
if (!$ctrl->hayStock($idProducto, $cantidadSolicitada)) {
    // No procesar el pedido
}
```
