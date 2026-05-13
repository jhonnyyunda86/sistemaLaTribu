<?php
/**
 * ReservaController
 * Gestiona la creación, cancelación y cambio de estado
 * de reservas. Sincroniza el estado de las mesas.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Mesa.php';

class ReservaController
{
    private Reserva $reservaModel;
    private Mesa    $mesaModel;

    public function __construct()
    {
        $db                 = (new Database())->conectar();
        $this->reservaModel = new Reserva($db);
        $this->mesaModel    = new Mesa($db);
    }

    /* =========================
       OBTENER TODAS (admin/mesero)
    ========================= */
    public function obtenerTodas(): array
    {
        return $this->reservaModel->obtenerTodos();
    }

    /* =========================
       OBTENER POR CLIENTE
    ========================= */
    public function obtenerPorCliente(int $idCliente): array
    {
        return $this->reservaModel->obtenerPorCliente($idCliente);
    }

    /* =========================
       OBTENER O CREAR ID_CLIENTE
    ========================= */
    public function resolverCliente(int $idUsuario, string $telefono = ''): int
    {
        $idCliente = $this->reservaModel->obtenerIdCliente($idUsuario);
        if (!$idCliente) {
            $idCliente = $this->reservaModel->crearCliente($idUsuario, $telefono);
        }
        return $idCliente;
    }

    /* =========================
       CREAR RESERVA
    ========================= */
    public function crear(int $idCliente, array $datos): array
    {
        $idMesa   = (int)($datos['id_mesa']        ?? 0);
        $fecha    = trim($datos['fecha']            ?? '');
        $hora     = trim($datos['hora']             ?? '');
        $personas = (int)($datos['numero_personas'] ?? 0);

        if (!$idMesa || !$fecha || !$hora || $personas < 1) {
            return ['ok' => false, 'msg' => 'Completa todos los campos.'];
        }

        if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
            return ['ok' => false, 'msg' => 'La fecha no puede ser en el pasado.'];
        }

        if ($this->reservaModel->mesaOcupadaEnFecha($idMesa, $fecha, $hora)) {
            return ['ok' => false, 'msg' => 'Esa mesa ya tiene una reserva en ese horario.'];
        }

        $mesa = $this->mesaModel->obtenerPorId($idMesa);
        if (!$mesa || $mesa['estado'] === 'mantenimiento') {
            return ['ok' => false, 'msg' => 'La mesa seleccionada no está disponible.'];
        }

        if ($personas > (int)$mesa['capacidad']) {
            return ['ok' => false, 'msg' => "La mesa #{$mesa['numero_mesa']} tiene capacidad máxima de {$mesa['capacidad']} personas."];
        }

        if (!$this->reservaModel->crear($idCliente, $idMesa, $fecha, $hora, $personas)) {
            return ['ok' => false, 'msg' => 'Error al guardar la reserva.'];
        }

        // Marcar mesa como reservada
        $this->mesaModel->actualizarEstado($idMesa, 'reservada');

        return ['ok' => true, 'msg' => "¡Reserva confirmada! Mesa #{$mesa['numero_mesa']} el {$fecha} a las {$hora}."];
    }

    /* =========================
       CANCELAR RESERVA (cliente)
    ========================= */
    public function cancelar(int $idReserva, int $idCliente, int $idMesa): array
    {
        if ($idReserva <= 0) {
            return ['ok' => false, 'msg' => 'ID de reserva inválido.'];
        }

        if (!$this->reservaModel->cancelar($idReserva, $idCliente)) {
            return ['ok' => false, 'msg' => 'No se pudo cancelar la reserva.'];
        }

        // Liberar la mesa
        if ($idMesa > 0) {
            $this->mesaModel->actualizarEstado($idMesa, 'disponible');
        }

        return ['ok' => true, 'msg' => 'Reserva cancelada. La mesa quedó disponible.'];
    }

    /* =========================
       CAMBIAR ESTADO (mesero/admin)
    ========================= */
    public function cambiarEstado(int $idReserva, int $idEstado): array
    {
        if ($idReserva <= 0 || $idEstado <= 0) {
            return ['ok' => false, 'msg' => 'Datos inválidos.'];
        }

        $sql  = "UPDATE reserva SET id_estado_reserva = :est WHERE id_reserva = :id";
        $stmt = (new Database())->conectar()->prepare($sql);
        $ok   = $stmt->execute([':est' => $idEstado, ':id' => $idReserva]);

        return $ok
            ? ['ok' => true,  'msg' => 'Estado de la reserva actualizado.']
            : ['ok' => false, 'msg' => 'Error al actualizar el estado.'];
    }
}
