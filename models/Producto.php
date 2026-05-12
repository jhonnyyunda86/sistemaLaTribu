<?php

class Producto {

    private $conn;
    private $tabla = "producto";

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    /* =========================
       CONTAR PRODUCTOS
    ========================= */
    public function contar() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->tabla}");
        return (int)$stmt->fetchColumn();
    }

    /* =========================
       OBTENER TODOS
    ========================= */
    public function obtenerTodos() {
        $sql = "SELECT * FROM {$this->tabla} ORDER BY id_producto DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       CREAR PRODUCTO
    ========================= */
    public function crear($nombre, $precio, $descripcion) {
        $sql = "INSERT INTO {$this->tabla} (nombre, precio, descripcion)
                VALUES (:nombre, :precio, :descripcion)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':descripcion', $descripcion);

        return $stmt->execute();
    }

    /* =========================
       OBTENER POR ID
    ========================= */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE id_producto = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       ACTUALIZAR
    ========================= */
    public function actualizar($id, $nombre, $precio, $descripcion) {
        $sql = "UPDATE {$this->tabla} 
                SET nombre = :nombre, precio = :precio, descripcion = :descripcion
                WHERE id_producto = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':descripcion', $descripcion);

        return $stmt->execute();
    }

    /* =========================
       ELIMINAR
    ========================= */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE id_producto = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>