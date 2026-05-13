# 👑 Módulo: Panel Administrador

**Archivos:**
- `views/dashboard/admin_dashboard.php`
- `views/dashboard/admin_menu.php`
- `views/dashboard/admin_mesas.php`
- `views/dashboard/admin_reservas.php`
- `views/dashboard/admin_pedidos.php`
- `views/dashboard/admin_usuarios.php`
- `views/dashboard/admin_inventario.php`
- `views/dashboard/admin_reportes.php`
- `views/dashboard/actualizar_estado_mesa.php`

**Modelos usados:** `UsuarioAdmin` · `Producto` · `Mesa` · `Reserva` · `Pedido` · `Inventario` · `Reporte`

---

## Acceso

```php
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php'); exit;
}
```

---

## `admin_dashboard.php` — Panel principal

**Funcionalidades:**
- Muestra 6 KPI cards: Clientes, Meseros, Productos, Mesas, Reservas, Pedidos.
- Modal para crear usuarios (admin/mesero/cliente) con validación completa.
- Accesos rápidos a todos los módulos.
- Estado del sistema: conexión BD, fecha del servidor, sesión activa.

**Proceso crear usuario:**
1. Valida campos, email, contraseñas coincidentes, mínimo 6 caracteres.
2. Verifica correo no duplicado con `UsuarioAdmin::existeCorreo()`.
3. Hashea contraseña y llama a `UsuarioAdmin::crear()`.

---

## `admin_menu.php` — Gestión de productos

**Funcionalidades:**
- Tabla de productos con columna de imagen (thumbnail 56×56px).
- Badge de stock por producto (Disponible / Bajo / Agotado / Sin registro).
- **Crear producto:** nombre, precio, descripción, imagen (upload), stock inicial, stock mínimo.
- **Editar producto:** mismos campos, imagen opcional (mantiene la actual si no se sube nueva).
- **Eliminar producto:** modal de confirmación.

**Upload de imágenes:**
```php
function subirImagenProducto(array $file): string|false
// Formatos: JPG, PNG, WEBP, GIF — Máximo 2 MB
// Guarda en: img/productos/prod_XXXXX.ext
// Retorna: nombre del archivo o false
```

Al crear, también crea el registro en `inventario` con stock inicial y mínimo.

---

## `admin_mesas.php` — Gestión de mesas

**Funcionalidades:**
- Grid de tarjetas por mesa con ícono de silla coloreado según estado.
- Crear mesa: número, capacidad, estado inicial.
- Cambiar estado via `<select>` con AJAX (sin recargar página).

**Estados:** `disponible` (verde) · `reservada` (ámbar) · `ocupada` (rojo) · `mantenimiento` (gris)

**AJAX — `actualizar_estado_mesa.php`:**
```
POST: id + estado
Respuesta: "ok" o "error"
```

---

## `admin_reservas.php` — Reservas (vista admin)

- Tabla con todas las reservas del sistema.
- Columnas: #, Fecha, Hora, Mesa, Personas, Cliente, Estado.
- Badges de estado con colores.

---

## `admin_pedidos.php` — Pedidos (vista admin)

- Tabla con todos los pedidos del sistema.
- Columnas: #, Fecha, Cliente, Estado.

---

## `admin_usuarios.php` — Gestión de usuarios

**Funcionalidades:**
- Muestra **todos los roles** (admin, mesero, cliente) en una sola tabla.
- Filtros por rol: Todos / Administradores / Meseros / Clientes.
- Buscador en tiempo real.
- **Crear usuario:** nombre, correo, teléfono, rol (los 3), contraseña.
- **Editar usuario:** mismos campos + cambio de contraseña opcional.
- **Activar/Desactivar:** toggle directo sin modal.
- **Eliminar:** modal de confirmación. Bloqueado para la propia cuenta del admin.

**Badges de rol:**
- Admin → morado
- Mesero → azul
- Cliente → naranja

---

## `admin_inventario.php` — Control de inventario

**Funcionalidades:**
- 4 KPI cards: Total productos, Agotados, Stock bajo, Entradas hoy.
- Tabla con barra de progreso de nivel de stock.
- Buscador + filtro por estado.
- **Agregar suministro:** modal con cantidad y descripción → actualiza stock + registra movimiento.
- **Editar stock mínimo:** modal con nuevo valor.
- **Registrar producto sin inventario:** modal que lista productos sin registro, permite asignar stock inicial y mínimo.
- Tabla de historial de movimientos (últimos 30).

---

## `admin_reportes.php` — Reportes y estadísticas

**Funcionalidades:**
- Filtros de periodo: Hoy / Esta semana / Este mes / Rango personalizado.
- 5 KPI cards: Total ventas, Facturas, Pedidos, Reservas, Ticket promedio.
- Gráfica de barras CSS de ventas por día.
- Top 5 productos más vendidos.
- Tablas: Facturas, Pedidos, Reservas del periodo.

**Exportar PDF:**
- Usa `jsPDF` + `jsPDF-AutoTable` (CDN).
- Genera PDF con encabezado naranja, todas las tablas y pie de página.
- Botón **Descargar** → `doc.save(nombreArchivo)`.
- Botón **Imprimir** → `doc.output('blob')` → abre en nueva pestaña → `window.print()`.

**Modelo `Reporte`:**
```php
resumen(desde, hasta)           // KPIs totales
ventasPorRango(desde, hasta)    // Facturas con cliente y método de pago
pedidosPorRango(desde, hasta)   // Pedidos con estado y total
reservasPorRango(desde, hasta)  // Reservas con mesa y cliente
productosMasVendidos(desde, hasta, limite)
ventasPorDia(desde, hasta)      // Para la gráfica
```
