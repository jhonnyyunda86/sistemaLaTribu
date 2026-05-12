<?php

class Usuario {

    private $conn;
    private $tabla = "usuario";

    /* =========================
       CONSTRUCTOR
    ========================= */
    public function __construct($db) {
        $this->conn = $db;
    }

    /* =========================
       LOGIN
    ========================= */
    public function login($correo, $password) {

        $sql = "SELECT * FROM {$this->tabla} WHERE correo = :correo LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }

        return false;
    }

    /* =========================
       OBTENER POR EMAIL
    ========================= */
    public function obtenerPorEmail($correo) {

        $sql = "SELECT * FROM {$this->tabla} WHERE correo = :correo LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       CONTAR POR ROL
    ========================= */
    public function contarPorRol($rol) {

        $sql = "SELECT COUNT(*) FROM {$this->tabla} WHERE role = :rol";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':rol', $rol);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /* =========================
       CREAR USUARIO
    ========================= */
    public function crear($data) {

        $sql = "INSERT INTO {$this->tabla} 
                (nombre, correo, telefono, role, password)
                VALUES (:nombre, :correo, :telefono, :role, :password)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':nombre'   => $data['nombre'],
            ':correo'   => $data['correo'],
            ':telefono' => $data['telefono'],
            ':role'     => $data['role'],
            ':password' => $data['password']
        ]);
    }

    /* =========================
       VERIFICAR CORREO EXISTENTE
    ========================= */
    public function existeCorreo($correo) {

        $sql = "SELECT id_usuario FROM {$this->tabla} WHERE correo = :correo LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }
}