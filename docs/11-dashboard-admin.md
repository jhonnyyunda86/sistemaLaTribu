# 🖥️ Dashboard Administrador

**Archivo:** `views/dashboard/admin_dashboard.php`  
**Acceso:** Solo rol `admin`  
**Modelo(s):** `UsuarioAdmin` · `Producto` · `Mesa` · `Reserva` · `Pedido`

---

## Propósito

Panel de control principal del administrador. Muestra un resumen estadístico del sistema y permite crear usuarios desde un modal sin salir de la página.

---

## Estructura visual

```
┌─────────────────────────────────────────────────────┐
│  ENCABEZADO OSCURO (glass)                          │
│  "Bienvenido, Administrador"  [Botón Agregar Usuario]│
└─────────────────────────────────────────────────────┘
┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│Clien.│ │Mese. │ │Prod. │ │Mesas │ │Reser.│ │Pedid.│
└──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘
┌──────────────────────────┐ ┌──────────────────────┐
│  ACCESOS RÁPIDOS (6)     │ │  ESTADO DEL SISTEMA  │
└──────────────────────────┘ └──────────────────────┘
```

---

## KPI Cards

Cada card muestra un ícono con degradado naranja y el conteo en tiempo real:

| Card | Método | Ícono |
|---|---|---|
| Clientes | `UsuarioAdmin::contarPorRol('cliente')` | `fa-users` |
| Meseros | `UsuarioAdmin::contarPorRol('mesero')` | `fa-user-tie` |
| Productos | `Producto::contar()` | `fa-utensils` |
| Mesas | `Mesa::contar()` | `fa-chair` |
| Reservas | `Reserva::contar()` | `fa-calendar-check` |
| Pedidos | `Pedido::contar()` | `fa-receipt` |

---

## Modal: Agregar Usuario

**Trigger:** Botón "Agregar Usuario" en el encabezado.  
**Acción POST:** `accion = crear_usuario`

### Campos del formulario
- Nombre completo
- Correo electrónico
- Teléfono
- Rol: Admin / Mesero / Cliente
- Contraseña + Confirmar contraseña

### Validaciones (PHP)
```php
// Campos obligatorios
if (!$nombre || !$correo || !$telefono || !$rol || !$password)

// Formato email
filter_var($correo, FILTER_VALIDATE_EMAIL)

// Contraseñas coinciden
$password !== $confirm

// Mínimo 6 caracteres
strlen($password) < 6

// Correo no duplicado
$usuarioAdmin->existeCorreo($correo)
```

### Comportamiento del modal
- Se reabre automáticamente si hubo error de validación (JS).
- Mantiene los valores del formulario tras un error.
- Se cierra con clic fuera del cuadro o tecla `Escape`.

---

## Accesos rápidos

6 cards con hover naranja que enlazan a:
- Gestionar menú → `admin_menu.php`
- Ver mesas → `admin_mesas.php`
- Reservas → `admin_reservas.php`
- Pedidos → `admin_pedidos.php`
- Clientes → `admin_usuarios.php`
- Usuarios → `admin_usuarios.php`
- Reportes → `admin_reportes.php`
- Inventario → `admin_inventario.php`

---

## Estado del sistema

- Badge verde: "Base de datos conectada"
- Fecha y hora del servidor: `date('d/m/Y H:i')`
- Badge naranja: "Sesión administrativa activa"

---

## CSS personalizado

```css
.dashboard-bg { padding: 0; border-radius: 0; }
/* El fondo lo maneja el body global del header.php */

.glass-card {
    background: rgba(255, 247, 237, 0.92);
    backdrop-filter: blur(14px);
    border: 1px solid rgba(251, 146, 60, 0.25);
}
.dark-glass {
    background: rgba(28, 25, 23, 0.78);
    backdrop-filter: blur(16px);
}
```

---

## JavaScript

```javascript
function abrirModal()  { /* muestra #modalUsuario */ }
function cerrarModal() { /* oculta #modalUsuario */ }

// Cierra al hacer clic fuera
document.getElementById('modalUsuario').addEventListener('click', ...)

// Cierra con Escape
document.addEventListener('keydown', ...)

// Reabre si hubo error
<?php if ($mensajeError): ?>
    document.addEventListener('DOMContentLoaded', abrirModal);
<?php endif; ?>
```
