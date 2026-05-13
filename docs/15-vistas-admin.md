# 👑 Vistas del Admin — Detalle técnico

**Archivos:**
- `views/dashboard/admin_menu.php`
- `views/dashboard/admin_mesas.php`
- `views/dashboard/admin_usuarios.php`
- `views/dashboard/admin_inventario.php`
- `views/dashboard/admin_reportes.php`
- `views/dashboard/admin_reservas.php`
- `views/dashboard/admin_pedidos.php`
- `views/dashboard/actualizar_estado_mesa.php`

---

# 🍔 `admin_menu.php` — Gestión del menú

**Modelo(s):** `Producto` · `Inventario`

---

## Función helper de upload

```php
function subirImagenProducto(array $file): string|false {
    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxBytes   = 2 * 1024 * 1024;  // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK)     return false;
    if (!in_array($file['type'], $permitidos)) return false;
    if ($file['size'] > $maxBytes)             return false;

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nombre  = 'prod_' . uniqid() . '.' . $ext;
    $destino = __DIR__ . '/../../img/productos/' . $nombre;

    return move_uploaded_file($file['tmp_name'], $destino) ? $nombre : false;
}
```

---

## Crear producto

```php
// POST: crear_producto = 1
// Campos: nombre, precio, descripcion, imagen (file), stock_inicial, stock_minimo

1. Valida nombre y precio no vacíos
2. Si hay archivo: subirImagenProducto() → $imagen = nombre_archivo
3. $productoModel->crear($nombre, $precio, $descripcion, $imagen)
4. $nuevoId = $db->lastInsertId()
5. INSERT INTO inventario (id_producto, stock_inicial, stock_minimo, CURDATE())
   ON DUPLICATE KEY UPDATE ...
6. Si stock_inicial > 0: registrarMovimiento('entrada', stock_inicial, 'Stock inicial')
```

---

## Editar producto

```php
// POST: editar_producto = 1
// Campos: id_producto, nombre, precio, descripcion, imagen (file, opcional)

1. Valida id, nombre, precio
2. Si hay archivo nuevo:
   a. subirImagenProducto() → $imagenNueva
   b. Obtiene imagen anterior: $prodActual['imagen']
   c. Si existe el archivo anterior: @unlink($rutaAnterior)
   d. $productoModel->actualizarImagen($id, $imagenNueva)
3. $productoModel->actualizar($id, $nombre, $precio, $descripcion)
   // No toca la imagen si no se subió nueva
```

---

## Tabla de productos

Columnas: Imagen · Producto · Precio · Descripción · Stock · Acciones

### Badge de stock
```php
$inv = $invModel->obtenerPorProducto($p['id_producto']);
if ($inv === false) {
    → "Sin registro" (gris)
} elseif ($stockActual === 0) {
    → "Agotado (0)" (rojo)
} elseif ($stockActual <= $stockMinimo) {
    → "Bajo (N)" (ámbar)
} else {
    → "N uds." (verde)
}
```

---

## Modales

### Modal Crear — zona de upload
```html
<div onclick="document.getElementById('file_crear').click()">
    <!-- Zona de drop visual con ícono nube -->
    <img id="preview_crear_img" class="hidden">
</div>
<input type="file" name="imagen" id="file_crear" class="hidden"
       onchange="previsualizarFile(this, 'preview_crear_img', 'preview_crear_wrap')">
```

### Modal Editar — imagen actual
```javascript
function abrirModalEditar(id, nombre, precio, descripcion, imagen) {
    // Si hay imagen actual: muestra preview y texto informativo
    // Si no: muestra zona de upload vacía
    // Limpia el input file para evitar subir la misma imagen accidentalmente
}
```

### Función de previsualización
```javascript
function previsualizarFile(input, imgId, wrapId) {
    var reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;  // Base64 de la imagen seleccionada
        img.classList.remove('hidden');
        wrap.style.display = 'none'; // Oculta la zona de drop
    };
    reader.readAsDataURL(file);
}
```

---

# 🪑 `admin_mesas.php` — Gestión de mesas

**Modelo(s):** `Mesa`

---

## Grid de mesas

Cada tarjeta muestra:
- Ícono de silla coloreado según estado.
- Número de mesa y capacidad.
- Badge de estado.
- `<select>` para cambiar estado via AJAX.

### Cambio de estado AJAX
```javascript
function cambiarEstado(id, estado) {
    fetch('./actualizar_estado_mesa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&estado=' + estado
    })
    .then(r => r.text())
    .then(data => {
        if (data !== 'ok') alert('Error al actualizar el estado');
    });
}
```

---

## `actualizar_estado_mesa.php` — Endpoint AJAX

```php
// No requiere sesión (endpoint interno)
// POST: id + estado
$mesaModel->actualizarEstado($id, $estado);
echo "ok"; // o "error"
```

> **Nota de seguridad:** Este endpoint no valida sesión. En producción debería verificar que el usuario esté autenticado.

---

# 👥 `admin_usuarios.php` — Gestión de usuarios

**Modelo(s):** `UsuarioAdmin`

---

## Acciones POST disponibles

| `accion` | Descripción |
|---|---|
| `crear` | Crea usuario con cualquier rol |
| `editar` | Actualiza datos + contraseña opcional |
| `toggle` | Activa/desactiva con `IF(activo=1,0,1)` |
| `eliminar` | Elimina usuario (bloqueado para la propia cuenta) |

---

## Protección de auto-eliminación

```php
$sesionId = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
if ($id === $sesionId) {
    $error = 'No puedes eliminar tu propia cuenta.';
}
```

En la tabla, el botón Eliminar se reemplaza por un candado:
```php
<?php if ((int)$u['id_usuario'] !== $sesionId): ?>
    <button>Eliminar</button>
<?php else: ?>
    <span title="No puedes eliminar tu propia cuenta">🔒 Tu cuenta</span>
<?php endif; ?>
```

---

## Filtros de la tabla

```javascript
var rolActivo = 'todos';

function filtrarRol(rol) {
    rolActivo = rol;
    // Actualiza estilos de botones
    filtrarTabla();
}

function filtrarTabla() {
    var q = buscador.value.toLowerCase();
    filas.forEach(fila => {
        var rolOk   = rolActivo === 'todos' || fila.dataset.rol === rolActivo;
        var textoOk = fila.textContent.toLowerCase().includes(q);
        fila.style.display = (rolOk && textoOk) ? '' : 'none';
    });
}
```

---

# 📊 `admin_reportes.php` — Reportes

**Modelo(s):** `Reporte`

---

## Cálculo del rango de fechas

```php
switch ($periodo) {
    case 'semana':
        $desde = date('Y-m-d', strtotime('monday this week'));
        $hasta = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'mes':
        $desde = date('Y-m-01');
        $hasta = date('Y-m-t');  // último día del mes
        break;
    case 'personalizado':
        $desde = $_GET['desde'] ?? $hoy;
        $hasta = $_GET['hasta'] ?? $hoy;
        break;
    default: // hoy
        $desde = $hasta = date('Y-m-d');
}
```

---

## Generación del PDF (jsPDF)

```javascript
function generarPDF(modo) {
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    // 1. Encabezado con degradado naranja
    doc.setFillColor(234, 88, 12);
    doc.rect(0, 0, W, 32, 'F');

    // 2. Tabla KPIs con autoTable
    doc.autoTable({ head: [['Indicador','Valor']], body: kpis, ... });

    // 3. Tabla facturas
    doc.autoTable({ head: [['#','Fecha','Cliente','Pedido','Método','Total']], ... });

    // 4. Tabla pedidos
    // 5. Tabla reservas
    // 6. Top productos

    // 7. Pie de página en todas las páginas
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.text('La Tribu — ' + fecha, 14, pageHeight - 8);
        doc.text('Página ' + i + ' de ' + totalPages, W - 14, pageHeight - 8, {align:'right'});
    }

    if (modo === 'imprimir') {
        const blob = doc.output('blob');
        const url  = URL.createObjectURL(blob);
        const win  = window.open(url, '_blank');
        win.addEventListener('load', () => win.print());
    } else {
        doc.save('reporte-latribu-' + periodo + '-' + fecha + '.pdf');
    }
}
```

### Datos PHP embebidos en JS
```php
// Los datos se pasan directamente como literales JS
const ventasRows = <?= json_encode(array_map(fn($v) => [
    $v['id_factura'],
    date('d/m/Y', strtotime($v['fecha'])),
    $v['cliente'] ?? '—',
    '#' . $v['id_pedido'],
    $v['metodo_pago'] ?? '—',
    '$' . number_format((float)$v['total_factura'], 2),
], $ventas)) ?>;
```

---

## CSS de impresión

```css
@media print {
    #sidebar, #top-header, #bottom-footer { display: none !important; }
    #app, #col-right, #content-area {
        display: block !important;
        height: auto !important;
        overflow: visible !important;
    }
    body { background: #fff !important; overflow: auto !important; }
}
```
