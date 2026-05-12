# Sistema Restaurante - La Tribu

Proyecto PHP con patrón MVC para gestionar un restaurante. Fue reorganizado tomando como referencia la estructura de `systemSchool`, pero manteniendo el tema y entidades del restaurante.

## Mejoras incluidas

- Estructura MVC más clara: `models`, `controllers`, `views/layouts`, `views/dashboard`, `public`.
- Login seguro con `password_hash` y `password_verify`.
- Corrección de inconsistencia `rol`/`role` en registro.
- Dashboards separados para administrador, mesero y cliente.
- Layout reutilizable con sidebar y header.
- Modelos base para usuarios, productos, mesas, reservas, pedidos e inventario.
- SQL corregido con claves foráneas, datos de prueba y columna `role` compatible con el código.

## Credenciales de prueba

La contraseña de todos los usuarios demo es: `123456`

- Admin: `admin@latribu.com`
- Mesero: `mesero@latribu.com`
- Cliente: `cliente@latribu.com`

## Instalación rápida

1. Crear/importar la base de datos ejecutando `sql/restaurante.sql`.
2. Revisar credenciales de conexión en `config/database.php`.
3. Abrir `public/index.php` o `views/usuarios/login.php` desde el servidor local.
