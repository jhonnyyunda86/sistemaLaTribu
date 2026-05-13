# 🛒 Dashboard Cliente

**Archivo:** `views/dashboard/cliente_dashboard.php`  
**Acceso:** Solo rol `cliente`  
**Modelo(s):** `Producto` · `Reserva`

---

## Propósito

Panel principal del cliente. Muestra el menú completo del restaurante con stock en tiempo real, permite agregar productos al carrito y realizar pedidos a domicilio con selección de método de pago.

---

## Estructura visual

```
┌─────────────────────────────────────────────────────┐
│  BIENVENIDA  "Hola, [nombre] 👋"   [Reservar mesa]  │
└─────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────┐
│  🔍 Buscador  │  [Todos] [Bebidas] [Parrilla] ...   │
└─────────────────────────────────────────────────────┘
┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ...
│ Card │ │ Card │ │ Card │ │ Card │ │ Card │
│Prod. │ │Prod. │ │Prod. │ │Prod. │ │Prod. │
└──────┘ └──────┘ └──────┘ └──────┘ └──────┘
                                    🛒 FAB (carrito)
```

---

## Proceso de pedido (POST)

```
accion = realizar_pedido
metodo_pago = 'Efectivo' | 'Tarjeta' | 'Nequi' | 'Daviplata' | 'Transferencia'
items = JSON: [{id, nombre, precio, cantidad, stock}]
```

### Flujo backend completo
```php
1. json_decode($itemsJson) → valida array no vacío
2. obtenerIdCliente($idUsuario) → o crearCliente() si no existe
3. INSERT INTO pedido (id_cliente, id_tipo_pedido=2, id_estado_pedido=1, NOW())
4. Por cada item:
   a. SELECT cantidad_actual FROM inventario WHERE id_producto = :id
   b. Si stock < cantidad → throw Exception (rollback)
   c. INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal)
5. SELECT SUM(subtotal) FROM detalle_pedido WHERE id_pedido = :id → $total
6. INSERT INTO factura (id_pedido, id_cliente, CURDATE(), metodo_pago, total)
7. commit() → $pedidoOk = true
```

> El trigger `trg_salida_inventario` descuenta el stock automáticamente al insertar en `detalle_pedido`.

---

## Cards de productos

### Datos que muestra cada card
```php
$prod['nombre']           // Nombre del producto
$prod['descripcion']      // Descripción
$prod['precio']           // Precio formateado
$prod['nombre_categoria'] // Badge de categoría (esquina superior izquierda)
$prod['estado_stock']     // disponible | bajo | agotado
$prod['stock']            // Número de unidades
$prod['imagen']           // Ruta local o fallback por categoría
```

### Lógica de imagen
```php
if (!empty($p['imagen'])) {
    if (str_starts_with($imgVal, 'http')) {
        $imgSrc = $imgVal;                    // URL externa
    } else {
        $rutaFisica = __DIR__ . '/../../img/productos/' . $imgVal;
        $imgSrc = file_exists($rutaFisica)
            ? '../../img/productos/' . $imgVal . '?v=' . filemtime($rutaFisica)
            : $imgFallback;                   // Fallback si no existe el archivo
    }
} else {
    $imgSrc = $imgFallback;                   // Fallback por categoría
}
```

### Fallbacks por categoría
```php
'Bebidas'  → foto de bebida (Unsplash)
'Parrilla' → foto de parrilla (Unsplash)
'Postres'  → foto de postre (Unsplash)
'Combos'   → foto de combo (Unsplash)
'Entradas' → foto de ensalada (Unsplash)
default    → foto de hamburguesa (Unsplash)
```

### Estados visuales
| Estado | Badge | Overlay | Botón |
|---|---|---|---|
| `disponible` | ✓ Verde | — | Naranja activo |
| `bajo` | ⚠ Ámbar | — | Naranja activo |
| `agotado` | ✕ Rojo | "AGOTADO" oscuro | Gris deshabilitado |

---

## Carrito (JavaScript)

### Estructura del carrito
```javascript
var carrito = {
    [id_producto]: {
        id: INT,
        nombre: STRING,
        precio: FLOAT,
        cantidad: INT,
        stock: INT      // límite máximo
    }
}
```

### Funciones del carrito
```javascript
agregarAlCarrito(id, nombre, precio, stock)
// Incrementa cantidad o crea entrada. Valida stock máximo.

quitarDelCarrito(id)
// Decrementa. Si llega a 0, elimina la entrada.

vaciarCarrito()
// Limpia el objeto y actualiza UI.

actualizarCarritoUI()
// Recalcula total, actualiza badge, renderiza items.
```

---

## Modal de pago

### Métodos disponibles
| Método | Ícono | Color |
|---|---|---|
| Efectivo | `fa-money-bill-wave` | Verde |
| Tarjeta débito/crédito | `fa-credit-card` | Azul |
| Nequi | `fa-mobile-screen` | Morado |
| Daviplata | `fa-wallet` | Amarillo |
| Transferencia bancaria | `fa-building-columns` | Azul claro |

### Flujo de selección
```javascript
function seleccionarMetodo(btn, metodo) {
    // 1. Quita clase 'seleccionado' de todos los botones
    // 2. Agrega 'seleccionado' al botón pulsado
    // 3. Cambia color del check a naranja
    // 4. Asigna valor a #input-metodo
    // 5. Serializa carrito a JSON en #input-items
    // 6. Habilita botón "Confirmar Pedido"
}
```

---

## Filtros del menú

### Buscador
```javascript
function filtrarProductos() {
    var q = buscador.value.toLowerCase();
    cards.forEach(card => {
        var catOk    = catActiva === 'todos' || card.dataset.cat === catActiva;
        var nombreOk = card.dataset.nombre.includes(q);
        card.style.display = (catOk && nombreOk) ? '' : 'none';
    });
}
```

### Categorías dinámicas
- Se generan desde `Producto::obtenerCategorias()`.
- Solo muestra categorías con al menos 1 producto.
- El slug de categoría se genera con `strtolower(preg_replace('/\s+/', '-', $nombre))`.
- Ejemplo: "Comidas rápidas" → `comidas-rápidas`.
