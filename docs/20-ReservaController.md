# 📅 ReservaController

**Archivo:** `controllers/ReservaController.php`  
**Modelos:** `Reserva` · `Mesa`  
**Acceso:** Cliente (crear/cancelar) · Mesero y Admin (cambiar estado)

---

## Propósito

Gestiona el ciclo de vida completo de una reserva: creación con validación de disponibilidad, cancelación con liberación de mesa, y cambio de estado por parte del mesero/admin. Mantiene sincronizado el estado de la mesa con el estado de la reserva.

---

## Constructor

```php
public function __construct()
{
    $db                 = (new Database())->conectar();
    $this->reservaModel = new Reserva($db);
    $this->mesaModel    = new Mesa($db);
}
```

Ambos modelos comparten la misma conexión PDO para garantizar consistencia.

---

## `obtenerTodas(): array`
Todas las reservas con JOIN a mesa, estado y cliente.  
Usado en: `admin_reservas.php` · `mesero_reservas.php`

## `obtenerPorCliente(int $idCliente): array`
Reservas de un cliente específico ordenadas por fecha DESC.  
Usado en: `cliente_reservas.php`

---

## `resolverCliente(int $idUsuario, string $telefono): int`

Obtiene el `id_cliente` del usuario en sesión. Si no tiene registro en la tabla `cliente`, lo crea automáticamente.

```php
$idCliente = $this->reservaModel->obtenerIdCliente($idUsuario);
if (!$idCliente) {
    $idCliente = $this->reservaModel->crearCliente($idUsuario, $telefono);
}
return $idCliente;
```

> Esto garantiza que cualquier usuario con rol `cliente` pueda reservar, incluso si fue creado por el admin sin pasar por el registro público.

---

## `crear(int $idCliente, array $datos): array`

**Campos esperados en `$datos`:**

| Campo | Tipo | Requerido |
|---|---|---|
| `id_mesa` | int | ✅ |
| `fecha` | string (Y-m-d) | ✅ |
| `hora` | string (H:i) | ✅ |
| `numero_personas` | int | ✅ |

### Cadena de validaciones

```
1. Campos completos
   → id_mesa, fecha, hora, personas >= 1

2. Fecha no en el pasado
   → strtotime($fecha) < strtotime(date('Y-m-d'))

3. Mesa no ocupada en ese horario (±2 horas)
   → Reserva::mesaOcupadaEnFecha(idMesa, fecha, hora)
   → Consulta: ABS(TIMESTAMPDIFF(MINUTE, ...)) < 120

4. Mesa no en mantenimiento
   → Mesa::obtenerPorId(idMesa) → estado !== 'mantenimiento'

5. Personas no exceden capacidad
   → personas > mesa['capacidad']
```

### Al confirmar exitosamente

```php
$this->reservaModel->crear($idCliente, $idMesa, $fecha, $hora, $personas);
// Estado inicial: 1 (Pendiente)

$this->mesaModel->actualizarEstado($idMesa, 'reservada');
// La mesa queda marcada como reservada inmediatamente
```

### Retorno
```php
['ok' => true,  'msg' => '¡Reserva confirmada! Mesa #X el FECHA a las HORA.']
['ok' => false, 'msg' => 'Descripción del error específico']
```

---

## `cancelar(int $idReserva, int $idCliente, int $idMesa): array`

Cancela una reserva del cliente y libera la mesa.

```php
// 1. Cancela la reserva (valida que pertenezca al cliente)
$this->reservaModel->cancelar($idReserva, $idCliente);
// SQL: UPDATE reserva SET id_estado_reserva = 3 WHERE id_reserva = :id AND id_cliente = :cid

// 2. Libera la mesa
$this->mesaModel->actualizarEstado($idMesa, 'disponible');
```

> La validación `AND id_cliente = :cid` en el modelo impide que un cliente cancele reservas de otro usuario.

---

## `cambiarEstado(int $idReserva, int $idEstado): array`

Usado por mesero y admin para cambiar el estado de cualquier reserva.

**Estados disponibles:**

| ID | Estado |
|---|---|
| 1 | Pendiente |
| 2 | Confirmada |
| 3 | Cancelada |

```php
// Ejecuta directamente sin validar propietario (mesero/admin tienen acceso total)
$sql = "UPDATE reserva SET id_estado_reserva = :est WHERE id_reserva = :id";
```

> A diferencia de `cancelar()`, este método no actualiza el estado de la mesa. El mesero gestiona el estado de la mesa por separado desde `admin_mesas.php`.

---

## Diagrama de estados de una reserva

```
[Pendiente] ──→ [Confirmada]
     │                │
     └──→ [Cancelada] ←┘

Al crear:   → Pendiente  (automático)
Al cancelar (cliente): → Cancelada + mesa → disponible
Al cambiar estado (mesero): cualquier transición
```

---

## Ejemplo de uso

```php
require_once __DIR__ . '/../../controllers/ReservaController.php';

$ctrl      = new ReservaController();
$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
$telefono  = $_SESSION['usuario']['telefono'] ?? '';

// Resolver cliente
$idCliente = $ctrl->resolverCliente($idUsuario, $telefono);

// Crear reserva
$resultado = $ctrl->crear($idCliente, $_POST);
if ($resultado['ok']) {
    $mensaje = $resultado['msg'];
} else {
    $error = $resultado['msg'];
}

// Cancelar
$resultado = $ctrl->cancelar(
    (int)$_POST['id_reserva'],
    $idCliente,
    (int)$_POST['id_mesa']
);
```
