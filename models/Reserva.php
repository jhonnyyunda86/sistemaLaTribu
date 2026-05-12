<?php

class Reserva {

    private $conn;
    private $tabla = "reserva";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* =========================
       CONTAR TOTAL
    ========================= */
    public function contar() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->tabla}");
        return (int)$stmt->fetchColumn();
    }

    /* =========================
       OBTENER TODAS (admin)
    ========================= */
    public function obtenerTodos() {
        $sql = "SELECT r.*, m.numero_mesa, m.capacidad, er.nombre_estado
                FROM {$this->tabla} r
                LEFT JOIN mesa m ON r.id_mesa = m.id_mesa
                LEFT JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
                ORDER BY r.id_reserva DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       OBTENER POR CLIENTE
    ========================= */
    public function obtenerPorCliente(int $idCliente): array {
        $sql = "SELECT r.*, m.numero_mesa, m.capacidad, er.nombre_estado
                FROM {$this->tabla} r
                LEFT JOIN mesa m ON r.id_mesa = m.id_mesa
                LEFT JOIN estado_reserva er ON r.id_estado_reserva = er.id_estado_reserva
                WHERE r.id_cliente = :id_cliente
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_cliente' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       OBTENER ID_CLIENTE por id_usuario
    ========================= */
    public function obtenerIdCliente(int $idUsuario): int|false {
        $sql  = "SELECT id_cliente FROM cliente WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_cliente'] : false;
    }

    /* =========================
       CREAR REGISTRO EN cliente
       (si el usuario no tiene uno)
    ========================= */
    public function crearCliente(int $idUsuario, string $telefono): int {
        $sql  = "INSERT INTO cliente (id_usuario, telefono) VALUES (:id, :tel)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idUsuario, ':tel' => $telefono]);
        return (int)$this->conn->lastInsertId();
    }

    /* =========================
       CREAR RESERVA
    ========================= */
    public function crear(int $idCliente, int $idMesa, string $fecha, string $hora, int $personas): bool {
        // estado 1 = Pendiente
        $sql = "INSERT INTO {$this->tabla}
                    (id_cliente, id_mesa, fecha_reserva, hora_reserva, numero_personas, id_estado_reserva)
                VALUES
                    (:id_cliente, :id_mesa, :fecha, :hora, :personas, 1)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id_cliente' => $idCliente,
            ':id_mesa'    => $idMesa,
            ':fecha'      => $fecha,
            ':hora'       => $hora,
            ':personas'   => $personas,
        ]);
    }

    /* =========================
       CANCELAR RESERVA
    ========================= */
    public function cancelar(int $idReserva, int $idCliente): bool {
        // estado 3 = Cancelada
        $sql  = "UPDATE {$this->tabla} SET id_estado_reserva = 3
                 WHERE id_reserva = :id AND id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $idReserva, ':id_cliente' => $idCliente]);
    }

    /* =========================
       VERIFICAR MESA DISPONIBLE
       en esa fecha/hora (±2h)
    ========================= */
    public function mesaOcupadaEnFecha(int $idMesa, string $fecha, string $hora): bool {
        $sql = "SELECT COUNT(*) FROM {$this->tabla}
                WHERE id_mesa = :id_mesa
                  AND fecha_reserva = :fecha
                  AND id_estado_reserva != 3
                  AND ABS(TIMESTAMPDIFF(MINUTE,
                        CONCAT(:fecha2,' ',:hora2),
                        CONCAT(fecha_reserva,' ',hora_reserva))) < 120";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_mesa' => $idMesa,
            ':fecha'   => $fecha,
            ':fecha2'  => $fecha,
            ':hora2'   => $hora,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
?>
