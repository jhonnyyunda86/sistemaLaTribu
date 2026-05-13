# 🧑‍🍳 Dashboard Mesero

**Archivo:** `views/dashboard/mesero_dashboard.php`  
**Acceso:** Solo rol `mesero`

---

## Propósito

Página de inicio del mesero. Muestra una bienvenida personalizada y accesos directos a las 4 secciones del panel del mesero.

---

## Estructura visual

```
┌─────────────────────────────────────────────────────┐
│  ENCABEZADO OSCURO (glass)                          │
│  "Bienvenido, [nombre]"          [Ícono Mesero]     │
└─────────────────────────────────────────────────────┘
┌──────────────────────┐ ┌──────────────────────────┐
│  🧾 Gestionar Pedidos│ │  📅 Reservas             │
└──────────────────────┘ └──────────────────────────┘
┌──────────────────────┐ ┌──────────────────────────┐
│  📦 Stock Productos  │ │  🪑 Ver Mesas            │
└──────────────────────┘ └──────────────────────────┘
```

---

## Accesos rápidos

| Card | Destino | Color del ícono |
|---|---|---|
| Gestionar Pedidos | `mesero_pedidos.php` | Naranja/ámbar |
| Reservas | `mesero_reservas.php` | Verde/esmeralda |
| Stock de Productos | `mesero_stock.php` | Morado/violeta |
| Ver Mesas | `admin_mesas.php` | Naranja/ámbar |

Cada card tiene:
- Ícono grande en cuadro redondeado con degradado.
- Título y descripción breve.
- Flecha derecha que se desplaza al hacer hover.
- Borde naranja al pasar el cursor.

---

## Notas

- El mesero comparte la vista de mesas con el admin (`admin_mesas.php`).
- No tiene acceso a reportes, usuarios ni inventario completo.
- El stock es de solo lectura (`mesero_stock.php`).

---

# 📋 Vista: Pedidos del Mesero

**Archivo:** `views/dashboard/mesero_pedidos.php`  
**Modelo(s):** `Pedido` · `Producto`

---

## Sección 1: KPI Cards

```php
// Calculados en PHP contando el array $pedidos
$kCounts = ['Pendiente'=>0, 'En preparación'=>0, 'Entregado'=>0, 'Cancelado'=>0];
foreach ($pedidos as $p) { $kCounts[$p['estado']]++; }
```

| Card | Color fondo | Color texto |
|---|---|---|
| Pendientes | Amarillo claro | Ámbar |
| En preparación | Azul claro | Azul |
| Entregados | Verde claro | Verde |
| Cancelados | Rojo claro | Rojo |

---

## Sección 2: Tabla de pedidos

### Query usada (`Pedido::obtenerTodosMesero()`)
```sql
SELECT p.id_pedido, p.fecha_pedido, p.id_mesero,
       ep.nombre_estado AS estado,
       tp.nombre_tipo   AS tipo,
       SUM(dp.subtotal) AS total,
       COUNT(dp.id_detalle) AS num_productos,
       u.nombre AS cliente,
       MAX(f.metodo_pago) AS metodo_pago
FROM pedido p
LEFT JOIN estado_pedido ep ...
LEFT JOIN tipo_pedido tp ...
LEFT JOIN detalle_pedido dp ...
LEFT JOIN cliente c ...
LEFT JOIN usuario u ...
LEFT JOIN factura f ...
GROUP BY p.id_pedido, ...
ORDER BY FIELD(ep.nombre_estado, 'Pendiente','En preparación','Entregado','Cancelado'),
         p.fecha_pedido DESC
```

> El `ORDER BY FIELD()` garantiza que los pendientes aparezcan siempre primero.

### Íconos por tipo de pedido
```javascript
'Domicilio'   → fa-motorcycle
'Para llevar' → fa-bag-shopping
default       → fa-chair  (Mesa)
```

---

## Sección 3: Modal detalle del pedido

**Carga:** AJAX a `mesero_pedido_detalle.php?pedido=ID`

```javascript
function verDetalleMesero(id) {
    // 1. Muestra modal con spinner
    // 2. fetch('mesero_pedido_detalle.php?pedido=' + id)
    // 3. Renderiza chips de info (estado, tipo, cliente, pago)
    // 4. Renderiza lista de productos con subtotales
    // 5. Muestra total
}
```

### Función `chip(icon, label, value, bg, color)`
Genera un bloque de información con fondo de color:
```javascript
chip('fa-tag', 'Estado', 'Pendiente', '#fef9c3', '#a16207')
// → <div style="background:#fef9c3">
//       <p>Estado</p>
//       <p style="color:#a16207">Pendiente</p>
//   </div>
```

---

## Sección 4: Modal cambio de estado

**Trigger:** Botón "Estado" en la tabla (solo en pedidos no finalizados).

```html
<form method="POST">
    <input type="hidden" name="accion"    value="cambiar_estado">
    <input type="hidden" name="id_pedido" id="est-id">
    <!-- Radio buttons: 1=Pendiente, 2=En preparación, 3=Entregado, 4=Cancelado -->
</form>
```

```javascript
function abrirCambioEstado(id, estadoActual, nombreEstado) {
    document.getElementById('est-id').value = id;
    // Preselecciona el radio del estado actual
    var map = {'Pendiente':'1', 'En preparación':'2', 'Entregado':'3', 'Cancelado':'4'};
    radios.forEach(r => r.checked = (r.value === map[estadoActual]));
}
```

---

## Sección 5: Modal nuevo pedido

### Selector de tipo
```javascript
function selTipo(btn, tipo) {
    // Resetea todos los botones a estilo inactivo
    // Activa el botón seleccionado con degradado naranja
    // Actualiza #np-tipo y #np-tipo-hidden
}
```

### Selección de productos
```javascript
function toggleProducto(card) {
    if (npCarrito[id]) {
        // Deselecciona: quita del carrito, oculta controles +/−
    } else {
        // Selecciona: agrega al carrito con cantidad=1, muestra controles
    }
    actualizarResumenNP();
}

function cambiarCant(id, delta) {
    // Incrementa/decrementa cantidad
    // Si llega a 0 → deselecciona el producto
    // Respeta el stock máximo disponible
}
```

### Envío del formulario
```javascript
// Antes de enviar, serializa el carrito a JSON
document.getElementById('np-items-hidden').value = JSON.stringify(Object.values(npCarrito));
```

---

# 📅 Vista: Reservas del Mesero

**Archivo:** `views/dashboard/mesero_reservas.php`

Ver documentación completa en `06-modulo-mesero.md`.

---

# 📦 Vista: Stock del Mesero

**Archivo:** `views/dashboard/mesero_stock.php`

Ver documentación completa en `06-modulo-mesero.md`.
