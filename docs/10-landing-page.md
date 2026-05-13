# 🏠 Módulo: Landing Page (index.php)

**Archivo:** `index.php`

---

## Descripción

Página de inicio pública del sistema. Es lo primero que ve cualquier visitante. Presenta el restaurante, sus servicios y el sistema de gestión, incentivando el registro e inicio de sesión.

---

## Lógica PHP al inicio

```php
session_start();
if (isset($_SESSION['usuario'])) {
    // Si ya hay sesión activa, redirige al dashboard según rol
    $destinos = [
        'admin'   => 'views/dashboard/admin_dashboard.php',
        'mesero'  => 'views/dashboard/mesero_dashboard.php',
        'cliente' => 'views/dashboard/cliente_dashboard.php',
    ];
    header('Location: ' . $destinos[$rol]);
    exit;
}
```

> Un usuario ya autenticado nunca ve la landing — va directo a su panel.

---

## Secciones

### 1. Navbar (fijo)
- Logo + nombre "La Tribu".
- Links de navegación: Inicio · Servicios · Menú · Nosotros.
- Botones: **Ingresar** (borde naranja) · **Registrarse** (relleno naranja).
- Menú hamburguesa para móvil.
- Efecto glass: `backdrop-filter: blur(14px)`.

### 2. Hero
- Fondo: imagen de restaurante con overlay oscuro.
- Título grande con degradado naranja en "Restaurante La Tribu".
- Descripción del sistema.
- Dos CTAs: "Ingresar al sistema" y "Crear cuenta".
- Imagen del restaurante con tarjeta flotante "Sabor Tribal".
- Animación AOS `fade-right` / `fade-left`.

### 3. Stats
- 4 métricas: 100% Gestión organizada · 24/7 Acceso · +50 Productos · Fast Pedidos.

### 4. Servicios
- 3 cards: Gestión de pedidos · Control de inventario · Usuarios y roles.
- Hover con elevación y sombra.

### 5. Menú (especialidades)
- 3 cards con imagen, nombre y descripción.
- Hamburguesa Tribal · Pizza Artesanal · Plato Especial.

### 6. Nosotros
- Imagen del restaurante + texto descriptivo.
- 2 puntos destacados: Procesos más rápidos · Acceso seguro.

### 7. CTA final
- Fondo oscuro con textura.
- Botón "Registrarme ahora" → `registre.php`.

### 8. Footer
- 4 columnas: Marca · Descripción · Enlaces · Acceso.
- Copyright 2026.

### 9. Chatbot
- Ver documento `09-chatbot.md`.

---

## Dependencias externas

| Librería | Versión | Uso |
|---|---|---|
| Tailwind CSS | CDN | Estilos utilitarios |
| Font Awesome | 6.4.0 | Íconos |
| AOS | 2.3.1 | Animaciones al hacer scroll |
| Google Fonts (Inter) | — | Tipografía |

---

## Configuración de Tailwind

```javascript
tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['Inter', 'sans-serif'] },
            colors: {
                tribu: {
                    cream: '#FFF7ED', orange: '#EA580C',
                    dark: '#1C1917',  brown: '#7C2D12', gold: '#F59E0B'
                }
            }
        }
    }
}
```

---

## Inicialización AOS

```javascript
AOS.init({ once: true, duration: 800, offset: 50 });
```

- `once: true` → la animación solo ocurre una vez.
- `duration: 800ms` → duración de cada animación.
