# 🗂️ Módulo: Modelos (Capa de datos)

**Archivos:** `models/*.php`

---

## Descripción

Capa de acceso a datos. Cada modelo encapsula las queries SQL de su entidad. Todos reciben una conexión PDO en el constructor y usan **prepared statements** para prevenir SQL injection.

---

## `Usuario.php`

| Método | Descripción |
|---|---|
| `login($correo, $password)` | Busca usuario por correo y verifica hash con `password_verify()` |
| `obtenerPorEmail($correo)` | Retorna fila completa por correo |
| `obtenerPorId(int $id)` | Retorna datos sin password |
| `crear($data)` | Inserta nuevo usuario |
| `existeCorreo($correo)` | Verifica si el correo ya está registrado |
| `actualizarPerfil(int $id, string $nombre, string $telefono)` | Actualiza datos personales |
| `cambiarCorreo(int $id, string $nuevoCorreo)` | Verifica que no esté en uso y actualiza |
| `cambiarPassword(int $id, string $actual, string $nueva)` | Verifica contraseña actual, hashea la nueva. Retorna `['ok', 'msg']` |
| `contarPorRol($rol)` | Cuenta usuarios por rol |

---

## `UsuarioAdmin.php`

Extiende la gestión de usuarios para el panel admin.

| Método | Descripción |
|---|---|
| `crear(array $datos)` | Inserta usuario con todos los campos |
| `existeCorreo(string $correo, int $excluirId)` | Verifica duplicado excluyendo el propio ID |
| `contarPorRol(string $rol)` | Para KPI cards del dashboard |
| `obtenerTodos()` | Todos los usuarios con detección dinámica de columna `activo` |
| `obtenerPorRol(string $rol)` | Filtra por rol con detección dinámica de `activo` |
| `obtenerPorId(int $id)` | Un usuario por ID |
| `actualizar(int $id, array $datos)` | Actualiza nombre, correo, teléfono, rol |
| `actualizarPassword(int $id, string $nueva)` | Hashea y actualiza contraseña |
| `toggleActivo(int $id)` | `IF(activo=1, 0, 1)` — activa/desactiva |
| `eliminar(int $id)` | Elimina usuario por ID |

> **Detección dinámica de `activo`:** Si la columna no existe en BD, usa `1 AS activo` como fallback para compatibilidad.

---

## `Producto.php`

| Método | Descripción |
|---|---|
| `contar()` | Total de productos |
| `obtenerTodos()` | Todos los productos ordenados por ID DESC |
| `crear($nombre, $precio, $descripcion, $imagen)` | Inserta producto. Detecta si columna `imagen` existe |
| `obtenerPorId($id)` | Un producto por ID |
| `actualizar($id, $nombre, $precio, $descripcion, $imagen)` | Actualiza. Si `imagen` es null/vacío, no la sobreescribe |
| `actualizarImagen(int $id, string $archivo)` | Actualiza solo la imagen |
| `eliminar($id)` | Elimina producto |
| `obtenerMenuCliente()` | JOIN con categoría e inventario. Calcula `estado_stock`. Detecta columna `imagen` |
| `obtenerCategorias()` | Categorías con conteo de productos |

---

## `Mesa.php`

| Método | Descripción |
|---|---|
| `contar()` | Total de mesas |
| `obtenerTodos()` | Todas las mesas |
| `crear($numero, $capacidad, $estado)` | Inserta mesa |
| `obtenerPorId($id)` | Una mesa por ID |
| `actualizar($id, $numero, $capacidad, $estado)` | Actualiza todos los campos |
| `actualizarEstado($id, $estado)` | Actualiza solo el estado |
| `eliminar($id)` | Elimina mesa |

---

## `Reserva.php`

| Método | Descripción |
|---|---|
| `contar()` | Total de reservas |
| `obtenerTodos()` | Todas las reservas con JOIN a mesa y estado |
| `obtenerPorCliente(int $idCliente)` | Reservas de un cliente específico |
| `obtenerIdCliente(int $idUsuario)` | Busca `id_cliente` por `id_usuario` |
| `crearCliente(int $idUsuario, string $telefono)` | Crea registro en tabla `cliente` |
| `crear(int $idCliente, int $idMesa, string $fecha, string $hora, int $personas)` | Crea reserva con estado 1 (Pendiente) |
| `cancelar(int $idReserva, int $idCliente)` | Cambia estado a 3 (Cancelada). Valida que pertenezca al cliente |
| `mesaOcupadaEnFecha(int $idMesa, string $fecha, string $hora)` | Verifica conflicto en rango ±2 horas |

---

## `Pedido.php`

| Método | Descripción |
|---|---|
| `contar()` | Total de pedidos |
| `obtenerTodos()` | Todos los pedidos (admin) |
| `obtenerTodosMesero()` | Pedidos con cliente, estado, tipo, total. Ordenados por estado |
| `obtenerIdMesero(int $idUsuario)` | Busca `id_mesero` por `id_usuario` |
| `historialCliente(int $idCliente)` | Pedidos de un cliente con total y método de pago |
| `detallePedido(int $idPedido, int $idCliente)` | Cabecera + productos. Valida que pertenezca al cliente |
| `detallePedidoMesero(int $idPedido)` | Cabecera + productos sin validar cliente |
| `cambiarEstado(int $idPedido, int $idEstado)` | Actualiza `id_estado_pedido` |
| `crearPedidoMesero(int $idMesero, int $idTipo, array $items, PDO $db)` | Transacción: crea pedido + detalles. Retorna `['ok', 'id_pedido', 'total']` |
| `statsCliente(int $idCliente)` | Total pedidos, total gastado, pedido mayor, último pedido |

---

## `Inventario.php`

| Método | Descripción |
|---|---|
| `stats()` | KPIs: total, agotados, stock bajo, entradas hoy |
| `obtenerTodos()` | Productos con estado calculado (disponible/bajo/agotado) |
| `obtenerPorId(int $id)` | Un registro de inventario |
| `obtenerPorProducto(int $idProducto)` | Inventario por producto |
| `agregarSuministro(int $idInv, int $cantidad, string $desc)` | Transacción: actualiza stock + registra movimiento entrada |
| `actualizarMinimo(int $idInv, int $minimo)` | Actualiza stock mínimo |
| `registrarMovimiento(int $idProd, string $tipo, int $cant, string $desc)` | Inserta en `movimiento_inventario`. Detecta columna `descripcion` |
| `historial(int $limite, ?int $idProducto)` | Últimos N movimientos. Detecta columna `descripcion` |
| `hayStock(int $idProducto, int $cantidad)` | Verifica si hay stock suficiente |
| `descontarStock(int $idProducto, int $cantidad, string $desc)` | Transacción: descuenta stock + registra salida |
| `contar()` | Total de registros en inventario |

---

## `Reporte.php`

| Método | Descripción |
|---|---|
| `ventasPorRango(string $desde, string $hasta)` | Facturas con cliente y pedido |
| `totalVentas(string $desde, string $hasta)` | Suma de `total_factura` |
| `pedidosPorRango(string $desde, string $hasta)` | Pedidos con estado, tipo y total calculado |
| `reservasPorRango(string $desde, string $hasta)` | Reservas con mesa y cliente |
| `productosMasVendidos(string $desde, string $hasta, int $limite)` | Top N por unidades vendidas |
| `ventasPorDia(string $desde, string $hasta)` | Agrupado por fecha para gráfica |
| `resumen(string $desde, string $hasta)` | Todos los KPIs en un solo array |

---

## Patrones comunes

### Detección dinámica de columnas
Usado cuando una columna puede no existir en BD (migración pendiente):
```php
try {
    $this->conn->query("SELECT columna FROM tabla LIMIT 1");
    $campo = "columna";
} catch (\PDOException $e) {
    $campo = "valor_fallback AS columna";
}
```

### Transacciones PDO
```php
$db->beginTransaction();
try {
    // operaciones...
    $db->commit();
    return ['ok' => true];
} catch (\Exception $e) {
    $db->rollBack();
    return ['ok' => false, 'msg' => $e->getMessage()];
}
```
