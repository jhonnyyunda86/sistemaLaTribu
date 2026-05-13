# 🛒 PedidoController

**Archivo:** `controllers/PedidoController.php`  
**Modelos:** `Pedido` · `Reserva`  
**Acceso:** Cliente (crear domicilio) · Mesero (crear en mesa) · Admin (ver todos)

---

## Propósito

Centraliza la lógica de creación y gestión de pedidos para dos actores distintos: el cliente (pedido a domicilio desde el menú) y el mesero (pedido en mesa/llevar desde el panel). Ambos flujos usan transacciones PDO para garantizar integridad.

---

## Constructor

```php
public function __construct()
{
    $this->db           = (new Database())->conectar();
    $this->pedidoModel  = new Pedido($this->db);
    $this->reservaModel = new Reserva($this->db);
}
```

`Reserva` se incluye para usar `obtenerIdCliente()` y `crearCliente()` en el flujo del cliente.

---

## Métodos de consulta

### `obtenerTodos(): array`
Todos los pedidos (admin). Sin JOINs adicionales.

### `obtenerTodosMesero(): array`
Pedidos con cliente, estado, tipo, total y método de pago.  
Ordenados: Pendiente → En preparación → Entregado → Cancelado.

### `historialCliente(int $idCliente): array`
Pedidos de un cliente con total calculado y método de pago.  
Agrupa por `id_pedido` para evitar duplicados por `GROUP BY`.

### `statsCliente(int $idCliente): array`
```php
// Retorna:
[
    'total_pedidos'  => INT,    // COUNT(DISTINCT id_pedido)
    'total_gastado'  => FLOAT,  // SUM de todos los subtotales
    'pedido_mayor'   => FLOAT,  // El pedido más caro
    'ultimo_pedido'  => STRING, // Fecha del más reciente
]
```

### `detallePedido(int $idPedido, int $idCliente): array`
Cabecera + productos. **Valida** que el pedido pertenezca al cliente.  
Retorna `[]` si no pertenece (seguridad).

### `detallePedidoMesero(int $idPedido): array`
Cabecera + productos. **Sin validar** propietario (mesero tiene acceso total).

---

## `crearPedidoCliente(int $idUsuario, string $metodoPago, array $items): array`

### Parámetros

| Parámetro | Descripción |
|---|---|
| `$idUsuario` | ID del usuario en sesión |
| `$metodoPago` | `'Efectivo'` · `'Tarjeta'` · `'Nequi'` · `'Daviplata'` · `'Transferencia'` |
| `$items` | Array de `[id, nombre, precio, cantidad, stock]` |

### Flujo completo (transacción)

```
beginTransaction()

1. Validar items no vacíos y método de pago
2. Obtener o crear id_cliente del usuario
3. INSERT INTO pedido
   → id_tipo_pedido = 2 (Domicilio)
   → id_estado_pedido = 1 (Pendiente)
4. Por cada item:
   a. SELECT cantidad_actual FROM inventario WHERE id_producto = :id
   b. Si stock < cantidad → throw Exception → rollBack()
   c. INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal)
   ↑ El trigger trg_salida_inventario descuenta el stock automáticamente
5. SELECT SUM(subtotal) FROM detalle_pedido WHERE id_pedido = :id → $total
6. INSERT INTO factura (id_pedido, id_cliente, CURDATE(), metodo_pago, total)

commit()
→ ['ok' => true, 'id_pedido' => X, 'msg' => '¡Pedido #X realizado con éxito!']
```

### Manejo de errores

```php
} catch (\Exception $e) {
    $this->db->rollBack();
    return ['ok' => false, 'msg' => $e->getMessage()];
}
// Ejemplo de msg: 'Stock insuficiente para uno de los productos.'
```

---

## `crearPedidoMesero(int $idUsuario, int $idTipo, array $items): array`

### Parámetros

| Parámetro | Descripción |
|---|---|
| `$idUsuario` | ID del mesero en sesión |
| `$idTipo` | `1` Mesa · `2` Domicilio · `3` Para llevar |
| `$items` | Array de `[id, nombre, precio, cantidad, stock]` |

### Diferencias con el flujo del cliente

| Aspecto | Cliente | Mesero |
|---|---|---|
| Tipo de pedido | Siempre Domicilio (2) | Configurable (1/2/3) |
| `id_cliente` | Se resuelve del usuario | No se asigna (NULL) |
| `id_mesero` | NULL | Se resuelve del usuario |
| Factura | Se crea con método de pago | No se crea |
| Validación stock | En el controlador | En el modelo |

```php
// Obtiene id_mesero del usuario en sesión
$idMesero = $this->pedidoModel->obtenerIdMesero($idUsuario);
if (!$idMesero) {
    return ['ok' => false, 'msg' => 'Tu usuario no tiene un registro de mesero.'];
}

// Delega al modelo que maneja su propia transacción
return $this->pedidoModel->crearPedidoMesero($idMesero, $idTipo, $items, $this->db);
```

### Retorno del modelo
```php
['ok' => true,  'id_pedido' => INT, 'total' => FLOAT]
['ok' => false, 'msg' => STRING]
```

---

## `cambiarEstado(int $idPedido, int $idEstado): array`

Actualiza `id_estado_pedido` en la tabla `pedido`.

**Estados disponibles:**

| ID | Estado |
|---|---|
| 1 | Pendiente |
| 2 | En preparación |
| 3 | Entregado |
| 4 | Cancelado |

> Al cambiar a estado `4` (Cancelado), el trigger `trg_restaurar_inventario` restaura automáticamente el stock de todos los productos del pedido.

---

## Diagrama de estados de un pedido

```
[Pendiente] ──→ [En preparación] ──→ [Entregado]
     │                  │
     └──────────────────┴──→ [Cancelado]
                              ↑
                    (trigger restaura stock)
```

---

## Ejemplo de uso

```php
require_once __DIR__ . '/../../controllers/PedidoController.php';

$ctrl = new PedidoController();

// Pedido del cliente (desde cliente_dashboard.php)
$items = json_decode($_POST['items'], true);
$resultado = $ctrl->crearPedidoCliente(
    (int)$_SESSION['usuario']['id_usuario'],
    $_POST['metodo_pago'],
    $items
);

// Pedido del mesero (desde mesero_pedidos.php)
$resultado = $ctrl->crearPedidoMesero(
    (int)$_SESSION['usuario']['id_usuario'],
    (int)$_POST['id_tipo_pedido'],
    json_decode($_POST['items'], true)
);

// Cambiar estado (mesero)
$resultado = $ctrl->cambiarEstado(
    (int)$_POST['id_pedido'],
    (int)$_POST['id_estado_pedido']
);
```
