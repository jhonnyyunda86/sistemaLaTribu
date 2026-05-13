# 👤 Módulo: Panel Cliente

**Archivos:**
- `views/dashboard/cliente_dashboard.php`
- `views/dashboard/cliente_reservas.php`
- `views/dashboard/cliente_historial.php`
- `views/dashboard/cliente_historial_detalle.php`
- `views/dashboard/cliente_cuenta.php`

**Modelos usados:** `Producto` · `Reserva` · `Pedido` · `Usuario`

---

## Acceso

```php
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'cliente') {
    header('Location: ../usuarios/login.php'); exit;
}
```

---

## `cliente_dashboard.php` — Menú y pedidos a domicilio

### Menú visual
- Grid responsive de cards de productos con imagen, nombre, descripción, precio y stock.
- Imágenes locales (`img/productos/`) con fallback por categoría si no hay imagen.
- Badge de estado sobre la imagen: ✓ Disponible / ⚠ Stock bajo / ✕ Agotado.
- Overlay "AGOTADO" en rojo cuando `stock = 0`.
- Botón "Agregar" deshabilitado si agotado.

### Filtros
- Buscador en tiempo real por nombre.
- Botones de categoría dinámicos con conteo de productos.

### Carrito flotante (FAB)
- Botón naranja fijo en esquina inferior derecha.
- Badge rojo con cantidad de productos.
- Modal deslizable desde abajo (móvil) / centrado (escritorio).
- Controles +/− por producto con validación de stock.
- Botón "Realizar Pedido a Domicilio".

### Modal de pago
- 5 métodos: Efectivo · Tarjeta · Nequi · Daviplata · Transferencia.
- Selección visual con check naranja.
- Botón "Confirmar Pedido" habilitado solo al seleccionar método.

### Proceso de pedido (POST)
```
accion = realizar_pedido
metodo_pago = string
items = JSON array [{id, nombre, precio, cantidad, stock}]
```

**Flujo backend:**
1. Obtiene o crea `id_cliente` del usuario en sesión.
2. Crea pedido con `id_tipo_pedido = 2` (Domicilio) y `id_estado_pedido = 1` (Pendiente).
3. Valida stock de cada producto antes de insertar `detalle_pedido`.
4. Calcula total con `SUM(subtotal)`.
5. Crea factura con método de pago y total.
6. Usa transacción PDO con rollback en caso de error.

---

## `cliente_reservas.php` — Reservas de mesa

### Formulario de reserva
- Selector de fecha (mínimo hoy).
- Selector de hora con grupos Almuerzo / Cena.
- Campo de número de personas.
- Grid visual de mesas con tarjetas coloreadas por estado.

### Lógica de mesas
- Al escribir personas, las mesas con capacidad insuficiente se atenúan.
- Al cambiar fecha/hora, se resetea la selección.
- Botón "Confirmar Reserva" habilitado solo cuando todos los campos están completos.

### Validaciones backend
- Fecha no puede ser en el pasado.
- Verifica disponibilidad con `Reserva::mesaOcupadaEnFecha()` (rango ±2 horas).
- Verifica que personas ≤ capacidad de la mesa.
- Al confirmar: cambia estado de la mesa a `reservada`.

### Cancelar reserva
- Botón por reserva activa.
- Al cancelar: cambia estado de la mesa a `disponible`.

### Mis reservas
- Lista de reservas del cliente con badge de estado.
- Muestra: mesa, fecha, hora, personas, estado.

---

## `cliente_historial.php` — Historial de compras

### KPI cards
- Total pedidos · Total gastado · Pedido mayor · Último pedido.

### Tabla de pedidos
- Columnas: #, Fecha/Hora, Tipo (con ícono), Productos, Método pago, Estado, Total.
- Buscador en tiempo real + filtro por estado.
- Botón "Ver" → abre modal con detalle del pedido.

### Modal de detalle
- Carga via AJAX a `cliente_historial_detalle.php`.
- Muestra: estado, tipo, método de pago, lista de productos con cantidad y subtotal.

---

## `cliente_historial_detalle.php` — Endpoint AJAX

```
GET: ?pedido=ID
Respuesta: JSON con cabecera + array de productos
Seguridad: valida que el pedido pertenezca al cliente en sesión
```

---

## `cliente_cuenta.php` — Mi cuenta

### Sección 1: Datos personales
- Edita nombre y teléfono.
- Correo y rol aparecen deshabilitados (solo lectura).
- Actualiza `$_SESSION['usuario']` tras guardar.

### Sección 2: Cambiar correo
**Validaciones:**
1. Correo actual debe coincidir con el registrado en BD.
2. Nuevo correo debe tener formato válido.
3. Nuevo correo ≠ correo actual.
4. Los dos campos del nuevo correo deben coincidir.
5. Correo nuevo no puede estar en uso por otra cuenta.

### Sección 3: Cambiar contraseña
**Flujo de doble autenticación:**
1. Ingresa correo registrado (verifica identidad).
2. Ingresa contraseña actual (verifica conocimiento).
3. Nueva contraseña + confirmación.

**Indicadores en tiempo real (JS):**
- ✓ Mínimo 6 caracteres.
- ✓ Al menos un número.
- ✓ Al menos una mayúscula.
- Mensaje de coincidencia entre nueva y confirmación.

**Botón ojo:** muestra/oculta cada campo de contraseña individualmente.
