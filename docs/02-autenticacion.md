# 🔐 Módulo: Autenticación

**Archivos:**
- `controllers/AuthController.php`
- `controllers/UsuarioController.php`
- `views/usuarios/login.php`
- `views/usuarios/registre.php`

---

## Descripción

Gestiona el inicio de sesión, registro de nuevos usuarios y cierre de sesión. Incluye protección anti-caché para evitar acceso a páginas protegidas tras cerrar sesión.

---

## `AuthController.php`

### Método `login()`

```
POST → controllers/AuthController.php
```

**Flujo:**
1. Valida que `email` y `password` no estén vacíos.
2. Valida formato de email con `FILTER_VALIDATE_EMAIL`.
3. Busca el usuario por correo en BD.
4. Verifica la contraseña con `password_verify()`.
5. Regenera el ID de sesión con `session_regenerate_id(true)`.
6. Guarda en `$_SESSION['usuario']`: `id_usuario`, `nombre`, `correo`, `role`.
7. Redirige según rol:

| Rol | Destino |
|---|---|
| `admin` | `admin_dashboard.php` |
| `mesero` | `mesero_dashboard.php` |
| `cliente` | `cliente_dashboard.php` |

### Método `logout()`

**Flujo:**
1. Vacía `$_SESSION = []`.
2. Destruye la cookie de sesión con `setcookie()`.
3. Llama a `session_destroy()`.
4. Envía headers anti-caché:
   ```
   Cache-Control: no-store, no-cache, must-revalidate
   Pragma: no-cache
   Expires: Sat, 01 Jan 2000 00:00:00 GMT
   ```
5. Redirige a `login.php`.

> **Por qué los headers:** Sin ellos, el navegador muestra la página en caché al presionar "atrás", dando la ilusión de seguir logueado.

---

## `UsuarioController.php`

### Método `registrar()`

```
POST → controllers/UsuarioController.php
```

**Validaciones:**
- Campos obligatorios: nombre, correo, password.
- Formato de email válido.
- Contraseña mínimo 6 caracteres.
- Teléfono requerido si el rol es `cliente`.
- Correo no duplicado en BD.

**Proceso:**
1. Hashea la contraseña con `password_hash($password, PASSWORD_DEFAULT)`.
2. Llama a `Usuario::crear()`.
3. Redirige con alerta SweetAlert2 de éxito o error.

---

## `views/usuarios/login.php`

- Redirige al dashboard si ya hay sesión activa.
- Formulario con campos `email` y `password`.
- Muestra alertas SweetAlert2 desde `$_SESSION['alert']`.
- Enlace a `registre.php`.

---

## `views/usuarios/registre.php`

- Redirige al dashboard si ya hay sesión activa.
- Formulario con: nombre, correo, teléfono, password.
- Campo oculto `role = cliente` (solo clientes se auto-registran).
- Muestra alertas SweetAlert2 con redirección opcional post-registro.

---

## Protección de páginas

Todas las vistas del dashboard verifican la sesión al inicio:

```php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuarios/login.php');
    exit;
}
// Para páginas exclusivas de un rol:
if ($_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php');
    exit;
}
```

El `header.php` envía headers anti-caché en **todas** las páginas protegidas:

```php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
```

---

## Sesión — estructura de `$_SESSION['usuario']`

```php
[
    'id_usuario' => 1,
    'nombre'     => 'Administrador',
    'correo'     => 'admin@latribu.com',
    'role'       => 'admin',   // 'admin' | 'mesero' | 'cliente'
    'telefono'   => '3000000000',
]
```
