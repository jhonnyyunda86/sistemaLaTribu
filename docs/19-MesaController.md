# 🪑 MesaController

**Archivo:** `controllers/MesaController.php`  
**Modelo:** `Mesa`  
**Acceso:** Admin y Mesero

---

## Propósito

Encapsula las operaciones sobre mesas del restaurante. El método más usado es `actualizarEstado()`, que es llamado tanto desde el formulario del admin como desde el endpoint AJAX del mesero.

---

## Constructor

```php
public function __construct()
{
    $this->mesaModel = new Mesa((new Database())->conectar());
}
```

---

## Métodos

### `obtenerTodas(): array`
Retorna todas las mesas ordenadas por `id_mesa DESC`.  
Usado en: `admin_mesas.php` · `cliente_reservas.php` · `mesero_stock.php`

### `obtenerPorId(int $id): array|false`
Retorna una mesa por su ID o `false` si no existe.  
Usado en: validación de capacidad al crear reservas.

---

## `crear(array $datos): array`

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `numero_mesa` | int | ✅ |
| `capacidad` | int | ✅ |
| `estado` | string | ❌ (default `disponible`) |

**Estados válidos:** `disponible` · `ocupada` · `reservada` · `mantenimiento`

```php
// Validación
if ($numero === '' || $capacidad === '')
    → ['ok' => false, 'msg' => 'El número y la capacidad son obligatorios.']
```

---

## `actualizarEstado(int $id, string $estado): array`

El método más importante del controlador. Valida que el estado sea uno de los 4 permitidos antes de actualizar.

```php
$estadosValidos = ['disponible', 'ocupada', 'reservada', 'mantenimiento'];

if ($id <= 0 || !in_array($estado, $estadosValidos))
    → ['ok' => false, 'msg' => 'Datos inválidos.']
```

**Usado en dos contextos:**

| Contexto | Cómo se llama |
|---|---|
| Formulario admin | POST desde `admin_mesas.php` |
| AJAX mesero/cliente | `actualizar_estado_mesa.php` (endpoint) |
| Al confirmar reserva | `ReservaController::crear()` → cambia a `reservada` |
| Al cancelar reserva | `ReservaController::cancelar()` → cambia a `disponible` |

---

## `actualizar(array $datos): array`

Actualiza todos los campos de una mesa (número, capacidad y estado).

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_mesa` | int | ✅ |
| `numero_mesa` | int | ✅ |
| `capacidad` | int | ✅ |
| `estado` | string | ❌ (default `disponible`) |

---

## `eliminar(int $id): array`

Elimina una mesa por ID. Retorna error si el ID es inválido o falla la operación.

> **Precaución:** Eliminar una mesa con reservas activas puede romper la integridad referencial si no hay `ON DELETE CASCADE` en la FK de `reserva`.

---

## Estados y su significado

| Estado | Descripción | Color en UI |
|---|---|---|
| `disponible` | Mesa libre para reservar o asignar | Verde |
| `reservada` | Tiene una reserva confirmada | Ámbar |
| `ocupada` | Hay clientes sentados actualmente | Rojo |
| `mantenimiento` | No disponible temporalmente | Gris |

---

## Ejemplo de uso

```php
require_once __DIR__ . '/../../controllers/MesaController.php';

$ctrl = new MesaController();

// Cambiar estado (desde AJAX)
$resultado = $ctrl->actualizarEstado((int)$_POST['id'], $_POST['estado']);
echo $resultado['ok'] ? 'ok' : 'error';

// Crear mesa
$resultado = $ctrl->crear($_POST);
if (!$resultado['ok']) $error = $resultado['msg'];
```
