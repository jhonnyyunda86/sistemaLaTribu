# ⚙️ Controladores

**Archivos:**
- `controllers/AuthController.php`
- `controllers/UsuarioController.php`

---

## Descripción general

Los controladores son el punto de entrada HTTP del sistema. Reciben peticiones POST/GET, ejecutan la lógica de negocio, interactúan con los modelos y redirigen al usuario con el resultado. No generan HTML — solo procesan y redirigen.

---

# `AuthController.php`

**Ruta de acceso:** `controllers/AuthController.php`  
**Modelo usado:** `Usuario`  
**Acceso:** Público (no requiere sesión)

---

## Punto de entrada

```php
// Al final del archivo — enruta según el parámetro GET
$controller = new AuthController();
($_GET['accion'] ?? 'login') === 'logout'
    ? $controller->logout()
    : $controller->login();
```

| URL | Acción ejecutada |
|---|---|
| `AuthController.php` | `login()` (por defecto) |
| `AuthController.php?accion=logout` | `logout()` |

---

## Dependencias cargadas

```php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
```

---

## Método privado: `alerta()`

```php
private function alerta(string $icon, string $title, string $text, string $to): void
```

**Propósito:** Centraliza el mecanismo de feedback al usuario. Guarda la alerta en sesión y redirige.

```php
$_SESSION['alert'] = [
    'icon'  => $icon,   // 'warning' | 'error' | 'success'
    'title' => $title,
    'text'  => $text,
];
header("Location: $to");
exit;
```

La vista de destino lee `$_SESSION['alert']` y lo muestra con **SweetAlert2**:
```php
// En login.php / registre.php:
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
// ...
if ($alert): ?>
<script>
Swal.fire({
    icon: '<?= $alert['icon'] ?>',
    title: '<?= $alert['title'] ?>',
    text: '<?= $alert['text'] ?>',
});
</script>
<?php endif;
```

---

## Método público: `login()`

**Trigger:** `POST` a `AuthController.php`  
**Formulario origen:** `views/usuarios/login.php`

### Flujo completo

```
1. Verificar método HTTP
   └── Si no es POST → redirigir a login.php

2. Recoger y limpiar datos
   $email    = trim($_POST['email']    ?? '')
   $password =      $_POST['password'] ?? ''

3. Validar campos vacíos
   └── Si vacíos → alerta('warning', 'Campos incompletos', ...)

4. Validar formato de email
   └── filter_var($email, FILTER_VALIDATE_EMAIL)
   └── Si inválido → alerta('error', 'Correo inválido', ...)

5. Buscar usuario en BD
   $usuario = Usuario::obtenerPorEmail($email)
   └── Si no existe → alerta('error', 'Credenciales incorrectas', ...)

6. Verificar contraseña
   password_verify($password, $usuario['password'])
   └── Si falla → alerta('error', 'Credenciales incorrectas', ...)
   └── Mismo mensaje para usuario no encontrado y contraseña incorrecta
       (evita enumerar usuarios válidos)

7. Regenerar ID de sesión (seguridad)
   session_regenerate_id(true)

8. Guardar datos en sesión
   $_SESSION['usuario'] = [
       'id_usuario' => ...,
       'nombre'     => ...,
       'correo'     => ...,
       'role'       => ...,
   ]

9. Redirigir según rol
   admin   → admin_dashboard.php
   mesero  → mesero_dashboard.php
   cliente → cliente_dashboard.php
   otro    → login.php (fallback)
```

### Seguridad aplicada

| Medida | Implementación |
|---|---|
| Mismo mensaje de error para usuario/contraseña | Evita enumerar usuarios válidos |
| `session_regenerate_id(true)` | Previene session fixation attacks |
| `password_verify()` | Verifica hash bcrypt, nunca texto plano |
| `trim()` en email | Evita espacios accidentales |

---

## Método público: `logout()`

**Trigger:** `GET` a `AuthController.php?accion=logout`  
**Enlace origen:** Sidebar (todos los roles) y footer

### Flujo completo

```
1. Vaciar datos de sesión
   $_SESSION = []
   // Más seguro que session_unset() — garantiza array vacío

2. Destruir cookie de sesión (si está habilitada)
   if (ini_get('session.use_cookies')) {
       $params = session_get_cookie_params();
       setcookie(
           session_name(),
           '',
           time() - 42000,    // Fecha en el pasado → el navegador la elimina
           $params['path'],
           $params['domain'],
           $params['secure'],
           $params['httponly']
       );
   }

3. Destruir la sesión en el servidor
   session_destroy()

4. Enviar headers anti-caché
   Cache-Control: no-store, no-cache, must-revalidate, max-age=0
   Cache-Control: post-check=0, pre-check=0
   Pragma: no-cache
   Expires: Sat, 01 Jan 2000 00:00:00 GMT

5. Redirigir a login.php
```

### Por qué 3 pasos para destruir la sesión

| Paso | Qué hace | Por qué es necesario |
|---|---|---|
| `$_SESSION = []` | Vacía los datos en memoria | Limpia inmediatamente los datos |
| `setcookie(..., time()-42000)` | Elimina la cookie del navegador | Sin esto, el navegador sigue enviando el ID de sesión antiguo |
| `session_destroy()` | Elimina el archivo de sesión del servidor | Sin esto, el ID antiguo podría reutilizarse |

### Por qué los headers anti-caché

Sin estos headers, el navegador guarda en caché las páginas del dashboard. Al presionar "atrás" después del logout, muestra la versión guardada sin consultar al servidor, dando la ilusión de seguir autenticado.

---

---

# `UsuarioController.php`

**Ruta de acceso:** `controllers/UsuarioController.php`  
**Modelo usado:** `Usuario`  
**Acceso:** Público (registro de nuevos clientes)

---

## Punto de entrada

```php
// Al final del archivo — siempre ejecuta registrar()
(new UsuarioController())->registrar();
```

No hay enrutamiento por parámetro — este controlador tiene una sola responsabilidad.

---

## Dependencias cargadas

```php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
```

---

## Método privado: `volver()`

```php
private function volver(array $alert): void
```

**Propósito:** Equivalente al `alerta()` de `AuthController`, pero siempre redirige a `registre.php`.

```php
$_SESSION['alert'] = $alert;
header("Location: ../views/usuarios/registre.php");
exit;
```

Acepta un campo adicional `redirect` para redirigir tras cerrar la alerta:
```php
$this->volver([
    'icon'     => 'success',
    'title'    => 'Registro exitoso',
    'text'     => 'Tu cuenta fue creada correctamente',
    'redirect' => 'login.php',   // ← redirige al login tras el OK
]);
```

En `registre.php` esto se maneja con:
```javascript
Swal.fire({...}).then(() => {
    <?php if (!empty($alert['redirect'])): ?>
        window.location.href = '<?= $alert['redirect'] ?>';
    <?php endif; ?>
});
```

---

## Método público: `registrar()`

**Trigger:** `POST` a `controllers/UsuarioController.php`  
**Formulario origen:** `views/usuarios/registre.php`

### Flujo completo

```
1. Verificar método HTTP
   └── Si no es POST → redirigir a registre.php

2. Recoger y limpiar datos
   $nombre   = trim($_POST['nombre']   ?? '')
   $correo   = trim($_POST['correo']   ?? '')
   $password =      $_POST['password'] ?? ''   ← sin trim (espacios válidos en passwords)
   $rol      = trim($_POST['role'] ?? $_POST['rol'] ?? 'cliente')
   $telefono = trim($_POST['telefono'] ?? '')

3. Validar campos obligatorios
   └── nombre, correo, password vacíos
   └── alerta: 'warning' / 'Campos incompletos'

4. Validar formato de correo
   └── filter_var($correo, FILTER_VALIDATE_EMAIL)
   └── alerta: 'error' / 'Correo inválido'

5. Validar longitud de contraseña
   └── strlen($password) < 6
   └── alerta: 'warning' / 'Contraseña inválida'

6. Validar teléfono para clientes
   └── Si $rol === 'cliente' && $telefono === ''
   └── alerta: 'warning' / 'Teléfono requerido'

7. Verificar correo no duplicado
   └── Usuario::existeCorreo($correo)
   └── alerta: 'error' / 'Correo existente'

8. Crear usuario
   $usuario->crear([
       'nombre'   => $nombre,
       'correo'   => $correo,
       'password' => password_hash($password, PASSWORD_DEFAULT),
       'role'     => $rol,
       'telefono' => $telefono,
   ])

9. Responder según resultado
   └── true  → alerta success + redirect a login.php
   └── false → alerta error
```

### Detalle de validaciones

| Validación | Condición | Tipo alerta | Mensaje |
|---|---|---|---|
| Campos vacíos | `nombre/correo/password === ''` | `warning` | Campos incompletos |
| Formato email | `!filter_var(...)` | `error` | Correo inválido |
| Contraseña corta | `strlen < 6` | `warning` | Contraseña inválida |
| Teléfono cliente | `rol=cliente && telefono=''` | `warning` | Teléfono requerido |
| Correo duplicado | `existeCorreo()` | `error` | Correo existente |

### Manejo del rol

```php
$rol = trim($_POST['role'] ?? $_POST['rol'] ?? 'cliente');
```

- Acepta tanto `role` como `rol` como nombre del campo (compatibilidad).
- Valor por defecto: `'cliente'`.
- El formulario público de registro envía `role = 'cliente'` como campo oculto.
- El admin puede crear usuarios con cualquier rol desde `admin_dashboard.php`.

### Seguridad en la contraseña

```php
// NO se aplica trim() a la contraseña
$password = $_POST['password'] ?? '';

// Se hashea con bcrypt antes de guardar
password_hash($password, PASSWORD_DEFAULT)
// PASSWORD_DEFAULT usa bcrypt con cost factor 10 por defecto
```

> **Por qué no trim() en password:** Los espacios al inicio/final de una contraseña son válidos y forman parte de ella. Eliminarlos cambiaría la contraseña que el usuario eligió.

---

## Comparación entre controladores

| Aspecto | `AuthController` | `UsuarioController` |
|---|---|---|
| Responsabilidad | Login + Logout | Registro de usuarios |
| Método de alerta | `alerta($icon, $title, $text, $to)` | `volver(array $alert)` |
| Destino de redirección | Variable (login o dashboard) | Siempre `registre.php` |
| Enrutamiento | Por `$_GET['accion']` | Sin enrutamiento (una sola acción) |
| Sesión | Lee y escribe | Solo escribe alertas |
| Modelo | `Usuario::obtenerPorEmail()` | `Usuario::existeCorreo()` + `crear()` |

---

## Diagrama de flujo general

```
Visitante
    │
    ├── GET  /index.php              → Landing page
    │
    ├── GET  /views/usuarios/login.php    → Formulario login
    │   └── POST → AuthController.php
    │               ├── Éxito → $_SESSION + redirect dashboard
    │               └── Error → $_SESSION['alert'] + redirect login
    │
    ├── GET  /views/usuarios/registre.php → Formulario registro
    │   └── POST → UsuarioController.php
    │               ├── Éxito → $_SESSION['alert'] + redirect login
    │               └── Error → $_SESSION['alert'] + redirect registre
    │
    └── GET  AuthController.php?accion=logout
                └── Destruye sesión + headers anti-caché + redirect login
```
