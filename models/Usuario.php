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
       OBTENER POR ID
    ========================= */
    public function obtenerPorId(int $id): array|false
    {
        $sql  = "SELECT id_usuario, nombre, correo, telefono, role, created_at
                 FROM {$this->tabla} WHERE id_usuario = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       ACTUALIZAR DATOS PERSONALES
    ========================= */
    public function actualizarPerfil(int $id, string $nombre, string $telefono): bool
    {
        $sql  = "UPDATE {$this->tabla}
                 SET nombre = :nombre, telefono = :telefono
                 WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nombre'   => $nombre,
            ':telefono' => $telefono,
            ':id'       => $id,
        ]);
    }

    /* =========================
       CAMBIAR CORREO
       Verifica que el correo nuevo no esté en uso
    ========================= */
    public function cambiarCorreo(int $id, string $nuevoCorreo): bool
    {
        // Verificar que no esté en uso por otro usuario
        $sql  = "SELECT COUNT(*) FROM {$this->tabla}
                 WHERE correo = :correo AND id_usuario != :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':correo' => $nuevoCorreo, ':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) return false;

        $sql  = "UPDATE {$this->tabla} SET correo = :correo WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':correo' => $nuevoCorreo, ':id' => $id]);
    }

    /* =========================
       CAMBIAR CONTRASEÑA
       Verifica la contraseña actual antes de cambiar
    ========================= */
    public function cambiarPassword(int $id, string $passwordActual, string $nuevaPassword): array
    {
        // Obtener hash actual
        $sql  = "SELECT password FROM {$this->tabla} WHERE id_usuario = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['ok' => false, 'msg' => 'Usuario no encontrado.'];
        }
        if (!password_verify($passwordActual, $row['password'])) {
            return ['ok' => false, 'msg' => 'La contraseña actual es incorrecta.'];
        }
        if (strlen($nuevaPassword) < 6) {
            return ['ok' => false, 'msg' => 'La nueva contraseña debe tener al menos 6 caracteres.'];
        }

        $sql  = "UPDATE {$this->tabla}
                 SET password = :pwd WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($sql);
        $ok   = $stmt->execute([
            ':pwd' => password_hash($nuevaPassword, PASSWORD_DEFAULT),
            ':id'  => $id,
        ]);

        return $ok
            ? ['ok' => true,  'msg' => 'Contraseña actualizada correctamente.']
            : ['ok' => false, 'msg' => 'Error al actualizar la contraseña.'];
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