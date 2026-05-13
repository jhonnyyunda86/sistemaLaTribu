# 📦 Módulo: Base de Datos

**Archivo principal:** `sql/restaurante.sql`  
**Archivos complementarios:** `sql/agregar_activo.sql` · `sql/inventario_setup.sql` · `sql/menu_cliente_setup.sql`  
**Configuración:** `config/database.php`

---

## Descripción

Motor de base de datos MySQL (`bdrestaurante`). Define todas las tablas, relaciones, restricciones y datos iniciales del sistema.

---

## Conexión — `config/database.php`

```php
class Database {
    private $host     = "127.0.0.1";
    private $port     = "3320";
    private $db_name  = "bdrestaurante";
    private $username = "root";
    private $password = "";

    public function conectar(): PDO
}
```

- Usa **PDO** con `ERRMODE_EXCEPTION`.
- Puerto configurable (Laragon usa `3320` por defecto).
- Retorna la conexión lista para usar en cualquier modelo.

---

## Tablas principales

| Tabla | Descripción |
|---|---|
| `usuario` | Usuarios del sistema (admin, mesero, cliente) |
| `cliente` | Extensión de usuario con teléfono y NIT |
| `mesero` | Extensión de usuario para meseros |
| `mesa` | Mesas del restaurante con estado |
| `producto` | Productos del menú con precio e imagen |
| `categoria_producto` | Categorías de productos |
| `inventario` | Stock actual y mínimo por producto |
| `movimiento_inventario` | Historial de entradas y salidas de stock |
| `reserva` | Reservas de mesas por cliente |
| `estado_reserva` | Estados: Pendiente, Confirmada, Cancelada |
| `pedido` | Pedidos realizados |
| `detalle_pedido` | Productos incluidos en cada pedido |
| `tipo_pedido` | Mesa, Domicilio, Para llevar |
| `estado_pedido` | Pendiente, En preparación, Entregado, Cancelado |
| `factura` | Factura generada por pedido con método de pago |
| `detalle_factura` | Detalle de productos facturados |

---

## Relaciones clave

```
usuario ──< cliente ──< reserva >── mesa
usuario ──< mesero
cliente ──< pedido >── mesero
pedido  ──< detalle_pedido >── producto
pedido  ──  factura
producto ── inventario
producto ──< movimiento_inventario
producto >── categoria_producto
```

---

## Columnas importantes

### `usuario`
```sql
id_usuario  INT PK AUTO_INCREMENT
nombre      VARCHAR(80)
correo      VARCHAR(80) UNIQUE
telefono    VARCHAR(20)
password    VARCHAR(255)   -- bcrypt hash
role        ENUM('admin','mesero','cliente')
activo      TINYINT(1) DEFAULT 1
created_at  TIMESTAMP
```

### `producto`
```sql
id_producto  INT PK
id_categoria INT FK
nombre       VARCHAR(80)
precio       DECIMAL(10,2)
descripcion  VARCHAR(180)
imagen       VARCHAR(255)   -- nombre de archivo local
```

### `inventario`
```sql
id_inventario      INT PK
id_producto        INT FK UNIQUE
cantidad_actual    INT DEFAULT 0
cantidad_minima    INT DEFAULT 0
fecha_actualizacion DATE
```

### `mesa`
```sql
estado ENUM('disponible','ocupada','reservada','mantenimiento')
```

---

## Scripts de migración

| Archivo | Propósito |
|---|---|
| `sql/agregar_activo.sql` | Agrega columna `activo` a `usuario` |
| `sql/inventario_setup.sql` | Agrega `descripcion` a `movimiento_inventario`, crea triggers, inicializa stock |
| `sql/menu_cliente_setup.sql` | Agrega columna `imagen` a `producto`, inserta categorías adicionales |

---

## Triggers SQL

### `trg_salida_inventario`
- **Evento:** `AFTER INSERT ON detalle_pedido`
- **Acción:** Descuenta `cantidad_actual` en `inventario` y registra movimiento tipo `salida`.
- **Protección:** Usa `GREATEST(0, cantidad_actual - cantidad)` para evitar stock negativo.

### `trg_restaurar_inventario`
- **Evento:** `AFTER UPDATE ON pedido`
- **Condición:** Cuando `id_estado_pedido` cambia a `4` (Cancelado).
- **Acción:** Restaura el stock de todos los productos del pedido y registra movimientos de `entrada`.

---

## Datos iniciales

```sql
-- Usuarios de prueba (password: 123456)
admin@latribu.com   → role: admin
mesero@latribu.com  → role: mesero
cliente@latribu.com → role: cliente

-- Categorías
Comidas rápidas · Bebidas · Parrilla · Postres · Combos · Entradas

-- Productos
Hamburguesa Tribu $22.000 · Salchipapa Especial $18.000
Limonada Natural $7.000   · Costillas BBQ $32.000

-- Mesas: 4 mesas con capacidades 4, 2, 6, 4
-- Estados de reserva: Pendiente · Confirmada · Cancelada
-- Estados de pedido: Pendiente · En preparación · Entregado · Cancelado
-- Tipos de pedido: Mesa · Domicilio · Para llevar
```
