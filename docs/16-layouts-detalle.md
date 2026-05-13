# 🧱 Layouts — Detalle técnico completo

**Archivos:**
- `views/layouts/header.php`
- `views/layouts/sidebar.php`
- `views/layouts/footer.php`

---

# `header.php`

---

## Responsabilidades completas

1. Inicia sesión PHP si no está activa.
2. Define la variable `$titulo` con valor por defecto.
3. Envía **4 headers HTTP anti-caché**.
4. Genera el `<!DOCTYPE html>` y `<head>` completo.
5. Carga todas las dependencias externas.
6. Define los estilos CSS del layout raíz.
7. Abre `<body>` y `<div id="app">`.

---

## Headers anti-caché

```php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
```

**Por qué son necesarios:**  
Sin estos headers, al cerrar sesión y presionar "atrás" en el navegador, el browser muestra la versión en caché de la página protegida sin consultar al servidor. Con estos headers, el navegador siempre solicita la página al servidor, que detecta la sesión destruida y redirige al login.

---

## Dependencias cargadas

```html
<!-- Tailwind CSS (utility-first CSS framework) -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Font Awesome 6.5 (íconos) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
```

---

## Fondo global

```css
body {
    background:
        linear-gradient(rgba(28,25,23,.90), rgba(28,25,23,.94)),
        url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?...');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;  /* El fondo no se mueve al hacer scroll */
}
```

---

## Sistema de layout (CSS crítico)

```css
/* Evita scroll en body — solo #content-area hace scroll */
html, body { height: 100%; overflow: hidden; }

/* Contenedor raíz: sidebar + columna derecha */
#app {
    display: flex;
    height: 100vh;
    overflow: hidden;
}

/* Sidebar: fijo a la izquierda, tiene su propio scroll si el menú es largo */
#sidebar {
    width: 288px;        /* w-72 de Tailwind */
    flex-shrink: 0;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 40;
}

/* Columna derecha: ocupa el resto del ancho */
#col-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-width: 0;        /* Evita overflow en flex */
}

/* Header: fijo arriba dentro de la columna derecha */
#top-header {
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 30;
}

/* Área de contenido: único elemento con scroll */
#content-area {
    flex: 1;             /* Crece para empujar el footer al fondo */
    overflow-y: auto;
    padding: 2rem;
}

/* Footer: siempre al fondo */
#bottom-footer {
    flex-shrink: 0;
    margin-top: auto;    /* Doble seguro: empuja al fondo si hay poco contenido */
}
```

---

## Scrollbar personalizada

```css
::-webkit-scrollbar          { width: 6px; }
::-webkit-scrollbar-track    { background: #1c1917; }
::-webkit-scrollbar-thumb    { background: linear-gradient(#ea580c, #f59e0b); border-radius: 999px; }
```

---

# `sidebar.php`

---

## Variables disponibles

```php
$usuario      = $_SESSION['usuario'] ?? [];
$rol          = $usuario['role']    ?? 'invitado';
$nombre       = $usuario['nombre']  ?? 'Usuario';
$paginaActual = basename($_SERVER['PHP_SELF']);
// Ejemplo: 'admin_menu.php', 'cliente_reservas.php'
```

---

## Detección de página activa

```php
// En cada link del menú:
class="menu-link <?= $paginaActual === 'admin_menu.php' ? 'active' : '' ?>"
```

La clase `active` aplica el degradado naranja al link actual.

---

## Estilos del sidebar

```css
#sidebar { background: linear-gradient(180deg, #1c1917 0%, #0f0c0a 100%); }

.menu-link {
    display: flex; align-items: center; gap: .85rem;
    padding: 12px 16px; border-radius: 14px;
    color: #fdba74;  /* naranja claro */
    font-weight: 600; font-size: .95rem;
    text-decoration: none;
    transition: all .22s ease;
}

.menu-link:hover {
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: #fff;
    transform: translateX(5px);  /* Deslizamiento al hover */
    box-shadow: 0 8px 20px rgba(234,88,12,.35);
}

.menu-link.active {
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: #fff;
    box-shadow: 0 6px 16px rgba(234,88,12,.3);
}
```

---

## Estructura del sidebar

```
┌─────────────────────────────┐
│  LOGO (La Tribu)            │  ← sidebar-logo-box con glass effect
├─────────────────────────────┤
│  AVATAR + NOMBRE + ROL      │  ← user-box
├─────────────────────────────┤
│  NAVEGACIÓN (según rol)     │  ← nav con overflow-y:auto
│  · Link 1                   │
│  · Link 2                   │
│  · ...                      │
├─────────────────────────────┤
│  [Cerrar sesión]            │  ← botón con degradado naranja
└─────────────────────────────┘
```

---

## Navegación por rol

### Admin (8 links)
```
Dashboard · Menú · Mesas · Reservas · Pedidos · Usuarios · Reportes · Inventario
```

### Mesero (5 links)
```
Inicio · Pedidos · Reservas · Stock · Mesas
```

### Cliente (4 links)
```
Menú · Reservar · Mis Compras · Mi Cuenta
```

---

## Apertura de la columna derecha

Al final del sidebar se abre la estructura de la columna derecha:

```html
<div id="col-right">
    <header id="top-header">
        <h1><?= $titulo ?></h1>
        <div><!-- Rol + Nombre del usuario --></div>
    </header>
    <div id="content-area">
    <!-- Aquí va el contenido de cada página -->
```

---

# `footer.php`

---

## Estructura completa

```html
</div><!-- /content-area -->

<footer id="bottom-footer">
    <!-- Franja principal: 3 columnas -->
    <div style="display:grid; grid-template-columns: repeat(3,1fr)">
        <!-- Columna 1: Marca -->
        <!-- Columna 2: Enlaces rápidos (según rol) -->
        <!-- Columna 3: Sesión activa -->
    </div>

    <!-- Franja copyright -->
    <div>© 2026 La Tribu · ❤️ · Íconos sociales</div>
</footer>

</div><!-- /col-right -->
</div><!-- /app -->
</body>
</html>
```

---

## Columna 2: Enlaces según rol

```php
$rol2 = $_SESSION['usuario']['role'] ?? 'cliente';
if ($rol2 === 'admin'):
    // Dashboard · Menú · Reservas · Reportes · Inventario · Cerrar sesión
elseif ($rol2 === 'mesero'):
    // Pedidos · Mesas · Cerrar sesión
else:
    // Menú · Reservar · Mis Compras · Mi Cuenta · Cerrar sesión
endif;
```

---

## Columna 3: Sesión activa

```php
// Avatar con inicial del nombre
strtoupper(substr($nombre ?? 'U', 0, 1))

// Nombre y rol
htmlspecialchars($nombre)
htmlspecialchars(ucfirst($rol))

// Fecha y hora del servidor
date('d/m/Y H:i')

// Ubicación fija
'Restaurante La Tribu · Colombia'
```

---

## Hover en links del footer

Los links usan eventos `onmouseover`/`onmouseout` inline en lugar de CSS para evitar conflictos con Tailwind:

```html
<a href="..."
   onmouseover="this.style.color='#fb923c'"
   onmouseout="this.style.color='#a8a29e'">
```

---

## Íconos sociales

```html
<i class="fa-brands fa-facebook"
   onmouseover="this.style.color='#fb923c'"
   onmouseout="this.style.color='#44403c'"></i>
<i class="fa-brands fa-instagram" ...></i>
<i class="fa-brands fa-whatsapp"
   onmouseover="this.style.color='#22c55e'"  <!-- Verde WhatsApp -->
   onmouseout="this.style.color='#44403c'"></i>
```
