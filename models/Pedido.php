<?php
class Pedido {
    private $conn; private $tabla = "pedido";
    public function __construct($db) { $this->conn = $db; }
    public function contar() { $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->tabla}"); return (int)$stmt->fetchColumn(); }
    public function obtenerTodos() { $stmt = $this->conn->query("SELECT * FROM {$this->tabla} ORDER BY id_pedido DESC"); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
}
?>
