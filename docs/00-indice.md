# 📚 Documentación — Sistema Restaurante La Tribu

**Versión:** 1.0  
**Stack:** PHP 8.1 · MySQL · Tailwind CSS · PDO · Laragon

---

## Índice de documentos

| # | Documento | Descripción |
|---|---|---|
| 01 | [Base de Datos](./01-base-de-datos.md) | Tablas, relaciones, triggers SQL y datos iniciales |
| 02 | [Autenticación](./02-autenticacion.md) | Login, registro, logout y protección de rutas |
| 03 | [Layout](./03-layout.md) | Header, Sidebar y Footer compartidos |
| 04 | [Panel Admin](./04-modulo-admin.md) | Dashboard, menú, mesas, usuarios, inventario, reportes |
| 05 | [Panel Cliente](./05-modulo-cliente.md) | Menú, pedidos, reservas, historial, cuenta |
| 06 | [Panel Mesero](./06-modulo-mesero.md) | Pedidos, reservas, stock |
| 07 | [Modelos](./07-modelos.md) | Capa de datos — todos los métodos de cada modelo |
| 08 | [Inventario](./08-inventario.md) | Flujo de stock, triggers y compatibilidad |
| 09 | [Chatbot](./09-chatbot.md) | Tribu Assistant — lógica, flujos y animaciones |
| 10 | [Landing Page](./10-landing-page.md) | index.php — secciones y dependencias |
| 11 | [Dashboard Admin](./11-dashboard-admin.md) | KPIs, modal crear usuario, accesos rápidos |
| 12 | [Dashboard Cliente](./12-dashboard-cliente.md) | Menú visual, carrito, pedidos, filtros |
| 13 | [Dashboard Mesero](./13-dashboard-mesero.md) | Pedidos, nuevo pedido, cambio de estado |
| 14 | [Vistas Cliente](./14-vistas-cliente.md) | Reservas, historial, detalle AJAX, cuenta |
| 15 | [Vistas Admin](./15-vistas-admin.md) | Menú, mesas, usuarios, reportes PDF |
| 16 | [Layouts — Detalle](./16-layouts-detalle.md) | Header, Sidebar y Footer — CSS, JS y lógica completa |
| 17 | [Controladores](./17-controladores.md) | AuthController y UsuarioController — flujos y seguridad |

---

## Estructura del proyecto

```
sistema-restaurante/
├── config/
│   └── database.php          # Conexión PDO a MySQL
├── controllers/
│   ├── AuthController.php    # Login / Logout
│   └── UsuarioController.php # Registro de clientes
├── models/
│   ├── Usuario.php           # Gestión de usuarios (perfil)
│   ├── UsuarioAdmin.php      # Gestión de usuarios (admin)
│   ├── Producto.php          # Productos del menú
│   ├── Mesa.php              # Mesas del restaurante
│   ├── Reserva.php           # Reservas de mesas
│   ├── Pedido.php            # Pedidos y detalles
│   ├── Inventario.php        # Control de stock
│   └── Reporte.php           # Consultas para reportes
├── views/
│   ├── layouts/
│   │   ├── header.php        # HTML head + apertura de layout
│   │   ├── sidebar.php       # Sidebar + header + apertura de content
│   │   └── footer.php        # Footer + cierre de layout
│   ├── usuarios/
│   │   ├── login.php         # Formulario de inicio de sesión
│   │   └── registre.php      # Formulario de registro
│   └── dashboard/
│       ├── admin_*.php       # Vistas del administrador
│       ├── cliente_*.php     # Vistas del cliente
│       └── mesero_*.php      # Vistas del mesero
├── img/
│   └── productos/            # Imágenes subidas de productos
├── sql/
│   ├── restaurante.sql       # Schema completo + datos iniciales
│   ├── agregar_activo.sql    # Migración: columna activo
│   ├── inventario_setup.sql  # Migración: triggers + descripcion
│   └── menu_cliente_setup.sql# Migración: columna imagen
├── public/
│   └── index.php             # Redirección a raíz
└── index.php                 # Landing page pública
```

---

## Roles del sistema

| Rol | Acceso |
|---|---|
| `admin` | Panel completo: usuarios, menú, mesas, reservas, pedidos, inventario, reportes |
| `mesero` | Pedidos (ver/crear/cambiar estado), reservas, stock (solo lectura), mesas |
| `cliente` | Menú, pedidos a domicilio, reservas, historial de compras, cuenta personal |

---

## Credenciales de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| admin@latribu.com | 123456 | Administrador |
| mesero@latribu.com | 123456 | Mesero |
| cliente@latribu.com | 123456 | Cliente |

---

## Migraciones pendientes (ejecutar en phpMyAdmin)

```sql
-- 1. Columna activo en usuario
ALTER TABLE usuario ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;

-- 2. Columna descripcion en movimiento_inventario
ALTER TABLE movimiento_inventario ADD COLUMN descripcion VARCHAR(200) NULL;

-- 3. Columna imagen en producto
ALTER TABLE producto ADD COLUMN imagen VARCHAR(255) NULL AFTER descripcion;

-- 4. Triggers de inventario (ver sql/inventario_setup.sql)
```

---

## Convenciones de código

- **Seguridad:** Todas las queries usan `prepare()` + `execute()` con parámetros nombrados.
- **Salida HTML:** Siempre `htmlspecialchars()` para prevenir XSS.
- **Contraseñas:** `password_hash()` con `PASSWORD_DEFAULT` al guardar, `password_verify()` al validar.
- **Transacciones:** Operaciones multi-tabla usan `beginTransaction()` / `commit()` / `rollBack()`.
- **Compatibilidad:** Columnas opcionales se detectan dinámicamente con try/catch para no romper en BD sin migrar.
