# 👥 UsuarioAdminController

**Archivo:** `controllers/UsuarioAdminController.php`  
**Modelo:** `UsuarioAdmin`  
**Acceso:** Solo Admin

---

## Propósito

Gestiona el CRUD completo de usuarios desde el panel del administrador. A diferencia de `UsuarioController` (que solo registra clientes públicamente), este controlador permite crear, editar, activar/desactivar y eliminar usuarios de cualquier rol (admin, mesero, cliente).

---

## Constructor

```php
public function __construct()
{
    $this->model = new UsuarioAdmin((new Database())->conectar());
}
```

---

## Métodos de consulta

### `obtenerTodos(): array`
Todos los usuarios de todos los roles.  
Detecta dinámicamente la columna `activo`.

### `obtenerPorRol(string $rol): array`
Filtra por rol: `'admin'` · `'mesero'` · `'cliente'`.

### `contarPorRol(string $rol): int`
Conteo para KPI cards del dashboard.

---

## `crear(array $datos): array`

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `nombre` | string | ✅ |
| `correo` | string (email) | ✅ |
| `telefono` | string | ✅ |
| `rol` | string | ✅ |
| `password` | string (min 6) | ✅ |
| `confirm` | string | ✅ |

### Cadena de validaciones

```
1. Todos los campos presentes
2. filter_var($correo, FILTER_VALIDATE_EMAIL)
3. $password === $confirm
4. strlen($password) >= 6
5. !existeCorreo($correo)  → correo no duplicado
```

### Al crear exitosamente

```php
$this->model->crear([
    'nombre'   => $nombre,
    'correo'   => $correo,
    'telefono' => $telefono,
    'role'     => $rol,
    'password' => password_hash($password, PASSWORD_DEFAULT),
]);
```

> La contraseña siempre se hashea con `PASSWORD_DEFAULT` (bcrypt). Nunca se guarda en texto plano.

---

## `editar(array $datos): array`

**Campos esperados:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_usuario` | int | ✅ |
| `nombre` | string | ✅ |
| `correo` | string (email) | ✅ |
| `telefono` | string | ❌ |
| `rol` | string | ✅ |
| `password` | string | ❌ (si vacío, no cambia) |
| `confirm` | string | ❌ (requerido si password no vacío) |

### Validación de correo al editar

```php
// Verifica que el correo no esté en uso por OTRO usuario
// (excluye el propio ID para permitir guardar sin cambiar el correo)
$this->model->existeCorreo($correo, $id)
// SQL: WHERE correo = :correo AND id_usuario != :id
```

### Cambio de contraseña opcional

```php
if (!empty($datos['password'])) {
    if ($np !== $cp)     return ['ok' => false, 'msg' => 'Las contraseñas no coinciden.'];
    if (strlen($np) < 6) return ['ok' => false, 'msg' => 'Mínimo 6 caracteres.'];
    $this->model->actualizarPassword($id, $np);
    // El modelo hashea internamente con password_hash()
}
```

---

## `toggleActivo(int $id): array`

Activa o desactiva un usuario con una sola operación SQL:

```sql
UPDATE usuario SET activo = IF(activo = 1, 0, 1) WHERE id_usuario = :id
```

No requiere conocer el estado actual — MySQL lo invierte automáticamente.

---

## `eliminar(int $id, int $sesionId): array`

### Protección de auto-eliminación

```php
if ($id === $sesionId) {
    return ['ok' => false, 'msg' => 'No puedes eliminar tu propia cuenta.'];
}
```

Esta validación se hace en el **controlador** (no en el modelo) porque requiere conocer el ID del usuario en sesión, que es contexto de la petición HTTP, no de la capa de datos.

---

## Diferencias con `UsuarioController`

| Aspecto | `UsuarioController` | `UsuarioAdminController` |
|---|---|---|
| Quién lo usa | Visitantes públicos | Solo administradores |
| Roles que puede crear | Solo `cliente` | `admin`, `mesero`, `cliente` |
| Operaciones | Solo crear | Crear, editar, toggle, eliminar |
| Feedback | SweetAlert2 via `$_SESSION['alert']` | Array `['ok', 'msg']` |
| Redirección | Siempre a `registre.php` | La vista decide |

---

## Ejemplo de uso

```php
require_once __DIR__ . '/../../controllers/UsuarioAdminController.php';

$ctrl     = new UsuarioAdminController();
$sesionId = (int)$_SESSION['usuario']['id_usuario'];

// Crear
$resultado = $ctrl->crear($_POST);

// Editar
$resultado = $ctrl->editar($_POST);

// Toggle activo
$resultado = $ctrl->toggleActivo((int)$_POST['id_usuario']);

// Eliminar (con protección de auto-eliminación)
$resultado = $ctrl->eliminar((int)$_POST['id_usuario'], $sesionId);

if ($resultado['ok']) {
    $mensaje = $resultado['msg'];
} else {
    $error = $resultado['msg'];
}
```
