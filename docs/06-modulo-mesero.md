# 🧑‍🍳 Módulo: Panel Mesero

**Archivos:**
- `views/dashboard/mesero_dashboard.php`
- `views/dashboard/mesero_pedidos.php`
- `views/dashboard/mesero_pedido_detalle.php`
- `views/dashboard/mesero_reservas.php`
- `views/dashboard/mesero_stock.php`

**Modelos usados:** `Pedido` · `Producto` · `Reserva`

---

## Acceso

```php
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'mesero') {
    header('Location: ../usuarios/login.php'); exit;
}
```

---

## `mesero_dashboard.php` — Inicio

- Bienvenida con nombre del mesero.
- 3 accesos rápidos: Gestionar Pedidos · Reservas · Stock de Productos · Ver Mesas.

---

## `mesero_pedidos.php` — Gestión de pedidos

### KPI cards
- Pendientes · En preparación · Entregados · Cancelados (calculados en PHP).

### Tabla de pedidos
- Muestra **todos** los pedidos del sistema (no solo los del mesero).
- Columnas: #, Fecha/Hora, Cliente, Tipo (con ícono), Productos, Total, Estado.
- Buscador en tiempo real + filtro por estado.
- Ordenados por: Pendiente → En preparación → Entregado → Cancelado.

### Botón "Ver"
- Abre modal con detalle del pedido cargado via AJAX.
- Muestra: estado, tipo, cliente, método de pago, lista de productos.

### Botón "Estado"
- Modal con radio buttons para cambiar estado.
- Estados disponibles: Pendiente · En preparación · Entregado · Cancelado.
- El radio del estado actual queda preseleccionado.
- Solo visible en pedidos no Entregados ni Cancelados.

### Botón "Nuevo Pedido"
- Modal con grid de productos del menú.
- Selector de tipo: Mesa · Domicilio · Para llevar.
- Buscador de productos dentro del modal.
- Tarjetas de producto con controles +/− de cantidad.
- Resumen con total en tiempo real.
- Botón "Crear Pedido" habilitado solo cuando hay al menos 1 producto.

**Proceso crear pedido (POST):**
```
accion = crear_pedido
id_tipo_pedido = 1|2|3
items = JSON array [{id, nombre, precio, cantidad, stock}]
```

**Flujo backend:**
1. Obtiene `id_mesero` del usuario en sesión.
2. Crea pedido con `id_estado_pedido = 1` (Pendiente).
3. Inserta cada `detalle_pedido`.
4. Usa transacción PDO con rollback.
5. Retorna `['ok' => true, 'id_pedido' => X, 'total' => Y]`.

---

## `mesero_pedido_detalle.php` — Endpoint AJAX

```
GET: ?pedido=ID
Respuesta: JSON con cabecera + array de productos
Seguridad: solo accesible con rol mesero
```

Usa `Pedido::detallePedidoMesero()` que no valida `id_cliente` (a diferencia del endpoint del cliente).

---

## `mesero_reservas.php` — Reservas

### Encabezado
- Contadores: Pendientes · Confirmadas · Canceladas.

### Tabla de reservas
- Columnas: #, Fecha (con badge HOY/PRÓXIMA), Hora, Mesa, Capacidad, Personas, Cliente, Estado.
- Badge "HOY" verde si `fecha_reserva = CURDATE()`.
- Badge "PRÓXIMA" azul si la fecha es futura.

### Filtros
- Por fecha (date picker).
- Por estado (select).
- Buscador por cliente o número de mesa.
- Botón "Limpiar" para resetear todos los filtros.

### Cambiar estado
- Modal con radio buttons: Pendiente · Confirmada · Cancelada.
- Radio del estado actual preseleccionado.
- Botón "Estado" no aparece en reservas ya Canceladas.

**Proceso (POST):**
```
accion = cambiar_estado
id_reserva = INT
id_estado_reserva = 1|2|3
```

---

## `mesero_stock.php` — Stock de productos

### Encabezado
- 4 contadores en el header oscuro: Total · Disponibles · Stock bajo · Agotados.

### Alertas automáticas
- Banner rojo si hay productos agotados.
- Banner amarillo si hay productos con stock bajo.

### Filtros
- 4 botones: Todos · Disponible · Stock bajo · Agotado.
- Buscador por nombre de producto.

### Grid de tarjetas
Cada tarjeta muestra:
- Nombre del producto y categoría.
- Badge de estado (Disponible / Stock bajo / Sin stock).
- Número grande de unidades con color según estado.
- Barra de progreso del nivel de stock.
- Stock mínimo configurado y precio.

**Colores por estado:**
| Estado | Fondo | Borde | Número |
|---|---|---|---|
| Disponible | Verde claro | Verde | Verde |
| Stock bajo | Amarillo claro | Amarillo | Ámbar |
| Agotado | Rojo claro | Rojo | Rojo |

> El mesero tiene acceso de **solo lectura** al inventario. No puede modificar stock.
