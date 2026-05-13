# 👤 Vistas del Cliente — Detalle técnico

**Archivos:**
- `views/dashboard/cliente_reservas.php`
- `views/dashboard/cliente_historial.php`
- `views/dashboard/cliente_historial_detalle.php`
- `views/dashboard/cliente_cuenta.php`

---

# 📅 `cliente_reservas.php` — Reservas de mesa

**Modelo(s):** `Reserva` · `Mesa`

---

## Flujo de obtención del cliente

```php
$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
$idCliente = $reservaModel->obtenerIdCliente($idUsuario);

// Si el usuario no tiene registro en tabla 'cliente', se crea automáticamente
if (!$idCliente) {
    $telefono  = $_SESSION['usuario']['telefono'] ?? '';
    $idCliente = $reservaModel->crearCliente($idUsuario, $telefono);
}
```

> Esto garantiza que cualquier usuario con rol `cliente` pueda reservar, incluso si fue creado por el admin sin registro en la tabla `cliente`.

---

## Formulario de reserva

### Campos
| Campo | Tipo | Validación |
|---|---|---|
| Fecha | `date` | `min = hoy`, no puede ser pasado |
| Hora | `select` | Grupos: Almuerzo (12:00–14:30) / Cena (18:00–21:30) |
| Personas | `number` | `min=1`, `max=20` |
| Mesa | `hidden` | Seleccionada visualmente |

### Validaciones backend
```php
// 1. Todos los campos completos
if (!$idMesa || !$fecha || !$hora || $personas < 1)

// 2. Fecha no en el pasado
if (strtotime($fecha) < strtotime(date('Y-m-d')))

// 3. Mesa no ocupada en ese horario (±2 horas)
if ($reservaModel->mesaOcupadaEnFecha($idMesa, $fecha, $hora))

// 4. Mesa no en mantenimiento
if ($mesa['estado'] === 'mantenimiento')

// 5. Personas no exceden capacidad
if ($personas > (int)$mesa['capacidad'])
```

### Al confirmar
```php
$reservaModel->crear($idCliente, $idMesa, $fecha, $hora, $personas);
$mesaModel->actualizarEstado($idMesa, 'reservada');
```

---

## Selector visual de mesas

Cada tarjeta de mesa tiene atributos `data-*` para el JS:
```html
<div class="mesa-card"
     data-id="1"
     data-estado="disponible"
     data-capacidad="4"
     data-numero="1">
```

### Comportamiento JS
```javascript
function seleccionarMesa(el) {
    if (el.classList.contains('bloqueada')) return; // ocupada/mantenimiento
    // Quita selección anterior
    // Agrega clase 'seleccionada' (borde naranja + fondo)
    // Actualiza #inp_mesa con el ID
    // Muestra texto "Mesa #X seleccionada"
    validarFormulario();
}

function filtrarMesasPorPersonas() {
    // Atenúa mesas con capacidad < personas ingresadas
    // Si la mesa seleccionada queda insuficiente, la deselecciona
}

function actualizarMesas() {
    // Al cambiar fecha/hora: resetea selección
}

function validarFormulario() {
    // Habilita botón solo si: fecha + hora + personas + mesa seleccionada
    btn.disabled = !(fecha && hora && personas && mesaSeleccionada);
}
```

---

## Lista "Mis Reservas"

Muestra las reservas del cliente con:
- Mesa y capacidad máxima.
- Fecha formateada `d/m/Y`.
- Hora en formato `HH:MM`.
- Número de personas.
- Badge de estado con color.
- Botón "Cancelar" (solo en reservas no canceladas).

### Cancelar reserva
```html
<form method="POST" onsubmit="return confirm('¿Cancelar esta reserva?')">
    <input type="hidden" name="accion"     value="cancelar">
    <input type="hidden" name="id_reserva" value="X">
    <input type="hidden" name="id_mesa"    value="Y">
</form>
```

```php
// Backend
$reservaModel->cancelar($idReserva, $idCliente);
$mesaModel->actualizarEstado($idMesaLiberar, 'disponible');
```

---

# 🛍️ `cliente_historial.php` — Historial de compras

**Modelo(s):** `Pedido` · `Reserva`

---

## KPI Cards

```php
$stats = $pedidoModel->statsCliente($idCliente);
// Retorna:
// total_pedidos  → COUNT(DISTINCT id_pedido)
// total_gastado  → SUM(subtotal) de todos los pedidos
// pedido_mayor   → MAX(subtotal_sum) — el pedido más caro
// ultimo_pedido  → fecha del pedido más reciente
```

---

## Tabla de pedidos

### Datos por fila
```php
$p['id_pedido']      // Número con # naranja
$p['fecha_pedido']   // Fecha + hora en dos líneas
$p['tipo']           // Con ícono (moto/bolsa/silla)
$p['num_productos']  // Badge naranja
$p['metodo_pago']    // Badge gris con ícono tarjeta
$p['estado']         // Badge de color
$p['total']          // Precio en naranja
```

### Íconos por tipo
```javascript
'Domicilio'   → fa-motorcycle
'Para llevar' → fa-bag-shopping
default       → fa-chair
```

### Atributos `data-*` para filtros
```html
<tr class="fila-hist"
    data-estado="Entregado"
    data-texto="pedido #5 domicilio entregado efectivo">
```

---

## Modal de detalle

**Carga:** AJAX a `cliente_historial_detalle.php?pedido=ID`

```javascript
function verDetalle(idPedido) {
    // 1. Abre modal con spinner
    // 2. fetch('cliente_historial_detalle.php?pedido=' + idPedido)
    // 3. Renderiza chips: estado, tipo, método, cantidad
    // 4. Renderiza productos con cantidad, precio unitario y subtotal
    // 5. Muestra total
}
```

### Función `chip(icon, label, value, bg, color)`
Igual que en el panel del mesero — genera bloques de información con fondo de color.

---

## Filtros

```javascript
function filtrarHistorial() {
    var q      = filtro-hist.value.toLowerCase();
    var estado = filtro-estado.value;

    filas.forEach(fila => {
        var textoOk  = fila.dataset.texto.includes(q);
        var estadoOk = !estado || fila.dataset.estado === estado;
        fila.style.display = (textoOk && estadoOk) ? '' : 'none';
    });
}
```

---

# 🔒 `cliente_historial_detalle.php` — Endpoint AJAX

```php
// Seguridad: solo mesero puede acceder
if ($_SESSION['usuario']['role'] !== 'cliente') { ... }

// Valida que el pedido pertenezca al cliente en sesión
$detalle = $pedidoModel->detallePedido($idPedido, $idCliente);
// Si no pertenece → retorna ['error' => 'Pedido no encontrado']

header('Content-Type: application/json; charset=utf-8');
echo json_encode($detalle);
```

**Respuesta JSON:**
```json
{
    "id_pedido": 5,
    "fecha_pedido": "2026-05-12 14:30:00",
    "estado": "Entregado",
    "tipo": "Domicilio",
    "metodo_pago": "Nequi",
    "total_factura": 40000,
    "productos": [
        { "nombre": "Hamburguesa Tribu", "cantidad": 1, "precio_unitario": 22000, "subtotal": 22000 },
        { "nombre": "Limonada Natural",  "cantidad": 2, "precio_unitario": 7000,  "subtotal": 14000 }
    ]
}
```

---

# ⚙️ `cliente_cuenta.php` — Mi cuenta

**Modelo(s):** `Usuario`

---

## Sección 1: Datos personales

```php
// POST: accion = perfil
$userModel->actualizarPerfil($idUsuario, $nombre, $telefono);
// También actualiza $_SESSION['usuario']['nombre'] y ['telefono']
```

Campos editables: nombre, teléfono.  
Campos de solo lectura: correo (con nota), rol.

---

## Sección 2: Cambiar correo

```php
// POST: accion = correo
// Validaciones en orden:
1. $correoActual !== ''
2. $usuarioActual['correo'] === $correoActual   // verifica identidad
3. filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)
4. $correoNuevo !== $correoConfirmar
5. $correoNuevo === $correoActual               // no puede ser igual
6. $userModel->cambiarCorreo($idUsuario, $correoNuevo)
   // Internamente verifica que no esté en uso por otro usuario
```

---

## Sección 3: Cambiar contraseña

```php
// POST: accion = password
// Validaciones en orden:
1. $correoVerif !== ''
2. $usuarioActual['correo'] === $correoVerif    // verifica identidad por correo
3. $passNueva !== $passConfirm
4. $userModel->cambiarPassword($idUsuario, $passActual, $passNueva)
   // Internamente: password_verify($actual, $hash) → si falla retorna error
   // Si pasa: password_hash($nueva, PASSWORD_DEFAULT)
```

### Indicadores en tiempo real (JS)
```javascript
function validarRequisitos(val) {
    setReq('req-len', val.length >= 6);          // Mínimo 6 caracteres
    setReq('req-num', /\d/.test(val));            // Al menos un número
    setReq('req-may', /[A-Z]/.test(val));         // Al menos una mayúscula
    validarCoincidencia();
}

function validarCoincidencia() {
    // Compara nueva vs confirmación en tiempo real
    // Verde si coinciden, rojo si no
}

function togglePass(inputId, btn) {
    // Alterna type="password" ↔ type="text"
    // Cambia ícono fa-eye ↔ fa-eye-slash
}
```
