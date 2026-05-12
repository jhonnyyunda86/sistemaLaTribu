<?php

class Mesa {

    private $conn;
    private $tabla = "mesa";

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    /* =========================
       CONTAR MESAS
    ========================= */
    public function contar() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->tabla}");
        return (int)$stmt->fetchColumn();
    }

    /* =========================
       OBTENER TODAS
    ========================= */
    public function obtenerTodos() {
        $sql = "SELECT * FROM {$this->tabla} ORDER BY id_mesa DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       CREAR MESA
    ========================= */
    public function crear($numero, $capacidad, $estado) {

        $sql = "INSERT INTO {$this->tabla} (numero_mesa, capacidad, estado)
                VALUES (:numero, :capacidad, :estado)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':capacidad', $capacidad);
        $stmt->bindParam(':estado', $estado);

        return $stmt->execute();
    }

    /* =========================
       OBTENER POR ID
    ========================= */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE id_mesa = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       ACTUALIZAR COMPLETO
    ========================= */
    public function actualizar($id, $numero, $capacidad, $estado) {

        $sql = "UPDATE {$this->tabla} 
                SET numero_mesa = :numero, capacidad = :capacidad, estado = :estado
                WHERE id_mesa = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':capacidad', $capacidad);
        $stmt->bindParam(':estado', $estado);

        return $stmt->execute();
    }

    /* =========================
       ACTUALIZAR SOLO ESTADO
    ========================= */
    public function actualizarEstado($id, $estado) {

        $sql = "UPDATE {$this->tabla} 
                SET estado = :estado
                WHERE id_mesa = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':estado', $estado);

        return $stmt->execute();
    }

    /* =========================
       ELIMINAR
    ========================= */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE id_mesa = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}

?>