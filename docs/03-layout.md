# 🧱 Módulo: Layout (Header, Sidebar, Footer)

**Archivos:**
- `views/layouts/header.php`
- `views/layouts/sidebar.php`
- `views/layouts/footer.php`

---

## Descripción

Sistema de layout compartido por todas las páginas del dashboard. Define la estructura visual fija: sidebar lateral, header superior y footer inferior. Usa **Flexbox** para que el contenido sea el único elemento con scroll.

---

## Estructura HTML resultante

```
#app (display:flex; min-height:100vh)
├── #sidebar          ← fijo a la izquierda, sticky
└── #col-right        ← columna derecha, flex-column
    ├── #top-header   ← fijo arriba
    ├── #content-area ← área con scroll (aquí va el contenido de cada página)
    └── #bottom-footer← fijo abajo
```

---

## `header.php`

**Responsabilidades:**
- Inicia sesión si no está iniciada.
- Envía headers HTTP anti-caché.
- Carga Tailwind CSS (CDN), Font Awesome 6.5, fuentes del sistema.
- Define el fondo global con imagen de restaurante + overlay oscuro.
- Abre el `<div id="app">`.

**CSS clave:**
```css
html, body { height: 100%; overflow: hidden; }
#app        { display: flex; height: 100vh; overflow: hidden; }
#sidebar    { width: 288px; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
#col-right  { flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
#top-header { flex-shrink: 0; position: sticky; top: 0; z-index: 30; }
#content-area { flex: 1; overflow-y: auto; padding: 2rem; }
#bottom-footer { flex-shrink: 0; margin-top: auto; }
```

> **Sticky footer:** `#content-area` tiene `flex:1` para crecer y empujar el footer al fondo. Con poco contenido el footer queda abajo; con mucho, baja con el scroll.

---

## `sidebar.php`

**Responsabilidades:**
- Lee `$_SESSION['usuario']` para obtener rol y nombre.
- Detecta la página actual con `basename($_SERVER['PHP_SELF'])` para marcar el link activo.
- Renderiza navegación diferente según el rol.
- Abre `#col-right`, `#top-header` y `#content-area`.

### Navegación por rol

**Admin:**
Dashboard · Menú · Mesas · Reservas · Pedidos · Usuarios · Reportes · Inventario

**Mesero:**
Inicio · Pedidos · Reservas · Stock · Mesas

**Cliente:**
Menú · Reservar · Mis Compras · Mi Cuenta

### Link activo
```php
$paginaActual = basename($_SERVER['PHP_SELF']);
// Ejemplo: 'admin_menu.php'
class="menu-link <?= $paginaActual==='admin_menu.php' ? 'active' : '' ?>"
```

---

## `footer.php`

**Responsabilidades:**
- Cierra `#content-area`.
- Renderiza el footer con 3 columnas: Marca · Enlaces rápidos (según rol) · Sesión activa.
- Cierra `#col-right`, `#app`, `<body>` y `<html>`.

### Columnas del footer

| Columna | Contenido |
|---|---|
| Marca | Logo + nombre + descripción del sistema |
| Enlaces rápidos | Links del menú según rol del usuario + cerrar sesión |
| Sesión activa | Avatar, nombre, rol, fecha/hora, ubicación |

### Franja de copyright
```
© 2026 La Tribu · Hecho con ❤️ para restaurantes colombianos
Íconos: Facebook · Instagram · WhatsApp
```

---

## Patrón de uso en cada vista

```php
$titulo = 'Nombre de la página';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<!-- Contenido de la página aquí -->

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

La variable `$titulo` es usada por el `<title>` del HTML y por el `#top-header`.
