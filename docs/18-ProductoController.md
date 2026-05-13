# 🍔 ProductoController

**Archivo:** `controllers/ProductoController.php`  
**Modelos:** `Producto` · `Inventario`  
**Acceso:** Solo admin (las vistas que lo usan verifican rol)

---

## Propósito

Encapsula toda la lógica de negocio relacionada con productos del menú: creación con imagen y stock inicial, edición con reemplazo de imagen, eliminación con limpieza de archivo, y consultas para el panel cliente y admin.

---

## Constructor

```php
public function __construct()
{
    $this->db            = (new Database())->conectar();
    $this->productoModel = new Producto($this->db);
    $this->invModel      = new Inventario($this->db);
}
```

Instancia ambos modelos con la misma conexión PDO para garantizar consistencia transaccional.

---

## Métodos de consulta

### `obtenerTodos(): array`
Retorna todos los productos ordenados por `id_producto DESC`.  
Usado en: `admin_menu.php`

### `obtenerMenuCliente(): array`
Retorna productos con JOIN a `categoria_producto` e `inventario`.  
Calcula `estado_stock` (disponible/bajo/agotado) directamente en SQL.  
Detecta dinámicamente si la columna `imagen` existe.  
Usado en: `cliente_dashboard.php` · `mesero_pedidos.php` · `mesero_stock.php`

### `obtenerCategorias(): array`
Retorna categorías con conteo de productos por cada una.  
Usado en: `cliente_dashboard.php` (filtros del menú)

---

## `crear(array $datos, array $archivo): array`

**Campos esperados en `$datos`:**

| Campo | Tipo | Requerido |
|---|---|---|
| `nombre` | string | ✅ |
| `precio` | decimal | ✅ |
| `descripcion` | string | ❌ |
| `stock_inicial` | int | ❌ (default 0) |
| `stock_minimo` | int | ❌ (default 5) |

**`$archivo`:** array `$_FILES['imagen']` (opcional)

### Flujo interno

```
1. Validar nombre y precio no vacíos
2. Si hay archivo → subirImagen() → obtiene nombre del archivo
3. Producto::crear(nombre, precio, descripcion, imagen)
4. $nuevoId = $db->lastInsertId()
5. INSERT INTO inventario (stock_inicial, stock_minimo)
   ON DUPLICATE KEY UPDATE ...
6. Si stock_inicial > 0 → registrarMovimiento('entrada', stock_inicial)
7. Retorna ['ok' => true, 'msg' => ..., 'id' => $nuevoId]
```

### Retorno
```php
['ok' => true,  'msg' => 'Producto agregado correctamente con stock inicial de X unidades.', 'id' => INT]
['ok' => false, 'msg' => 'Descripción del error']
```

---

## `actualizar(array $datos, array $archivo): array`

**Campos esperados en `$datos`:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_producto` | int | ✅ |
| `nombre` | string | ✅ |
| `precio` | decimal | ✅ |
| `descripcion` | string | ❌ |

**`$archivo`:** array `$_FILES['imagen']` (opcional — si vacío, mantiene imagen actual)

### Flujo interno

```
1. Validar id, nombre, precio
2. Si hay archivo nuevo:
   a. subirImagen() → $imagenNueva
   b. Obtener imagen anterior del producto
   c. Si existe el archivo anterior → @unlink() (elimina del disco)
   d. Producto::actualizarImagen(id, $imagenNueva)
3. Producto::actualizar(id, nombre, precio, descripcion)
   → No toca la columna imagen (solo actualiza datos básicos)
4. Retorna resultado
```

> **Por qué dos operaciones separadas:** `actualizar()` del modelo no modifica la imagen para evitar sobreescribirla accidentalmente. `actualizarImagen()` es una operación explícita y separada.

---

## `eliminar(int $id): array`

```
1. Validar id > 0
2. Obtener producto para conocer el nombre del archivo de imagen
3. Si tiene imagen y el archivo existe → @unlink() (limpia el disco)
4. Producto::eliminar(id)
5. Retorna resultado
```

> El `@` en `@unlink()` suprime errores si el archivo ya no existe en disco.

---

## `subirImagen(array $file): string|false` *(privado)*

```php
$permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxBytes   = 2 * 1024 * 1024;  // 2 MB

// Validaciones:
$file['error'] !== UPLOAD_ERR_OK   → false
!in_array($file['type'], $permitidos) → false
$file['size'] > $maxBytes          → false

// Nombre único:
$nombre = 'prod_' . uniqid() . '.' . $ext;
// Ejemplo: prod_6a03e9c92bad8.webp

// Destino:
__DIR__ . '/../img/productos/' . $nombre

// Retorna: nombre del archivo (string) o false
```

---

## Ejemplo de uso desde una vista

```php
require_once __DIR__ . '/../../controllers/ProductoController.php';

$ctrl = new ProductoController();

// Crear
$resultado = $ctrl->crear($_POST, $_FILES['imagen'] ?? []);
if ($resultado['ok']) {
    $mensaje = $resultado['msg'];
} else {
    $error = $resultado['msg'];
}

// Obtener menú para cliente
$productos = $ctrl->obtenerMenuCliente();
```
