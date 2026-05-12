<?php

require_once __DIR__ . '/../config/database.php';

class UsuarioAdmin
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* =========================
       CREAR USUARIO
    ========================= */
    public function crear(array $datos): bool
    {
        $sql = "INSERT INTO usuario (nombre, correo, telefono, role, password)
                VALUES (:nombre, :correo, :telefono, :role, :password)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'   => $datos['nombre'],
            ':correo'   => $datos['correo'],
            ':telefono' => $datos['telefono'],
            ':role'     => $datos['role'],
            ':password' => $datos['password'],
        ]);
    }

    /* =========================
       VERIFICAR CORREO
    ========================= */
    public function existeCorreo(string $correo, int $excluirId = 0): bool
    {
        $sql  = "SELECT COUNT(*) FROM usuario WHERE correo = :correo AND id_usuario != :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => $correo, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /* =========================
       CONTAR POR ROL
    ========================= */
    public function contarPorRol(string $rol): int
    {
        $sql  = "SELECT COUNT(*) FROM usuario WHERE role = :role";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => $rol]);

        return (int) $stmt->fetchColumn();
    }

    /* =========================
       OBTENER TODOS
    ========================= */
    public function obtenerTodos(): array
    {
        try {
            $this->db->query("SELECT activo FROM usuario LIMIT 1");
            $campos = "id_usuario, nombre, correo, telefono, role, activo, created_at";
        } catch (\PDOException $e) {
            $campos = "id_usuario, nombre, correo, telefono, role, 1 AS activo, created_at";
        }

        $sql  = "SELECT {$campos} FROM usuario ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       OBTENER POR ROL
    ========================= */
    public function obtenerPorRol(string $rol): array
    {
        // Verificamos si la columna 'activo' existe para compatibilidad
        // con bases de datos que aún no han ejecutado la migración
        try {
            $test = $this->db->query("SELECT activo FROM usuario LIMIT 1");
            $tieneActivo = true;
        } catch (\PDOException $e) {
            $tieneActivo = false;
        }

        $campos = $tieneActivo
            ? "id_usuario, nombre, correo, telefono, activo, created_at"
            : "id_usuario, nombre, correo, telefono, 1 AS activo, created_at";

        $sql  = "SELECT {$campos}
                 FROM usuario
                 WHERE role = :role
                 ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => $rol]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       OBTENER POR ID
    ========================= */
    public function obtenerPorId(int $id): array|false
    {
        $sql  = "SELECT id_usuario, nombre, correo, telefono, role, activo, created_at
                 FROM usuario
                 WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       ACTUALIZAR DATOS
    ========================= */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "UPDATE usuario
                SET nombre   = :nombre,
                    correo   = :correo,
                    telefono = :telefono,
                    role     = :role
                WHERE id_usuario = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'   => $datos['nombre'],
            ':correo'   => $datos['correo'],
            ':telefono' => $datos['telefono'],
            ':role'     => $datos['role'],
            ':id'       => $id,
        ]);
    }

    /* =========================
       ACTUALIZAR CONTRASEÑA
    ========================= */
    public function actualizarPassword(int $id, string $nuevaPassword): bool
    {
        $sql  = "UPDATE usuario SET password = :password WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':password' => password_hash($nuevaPassword, PASSWORD_DEFAULT),
            ':id'       => $id,
        ]);
    }

    /* =========================
       ACTIVAR / DESACTIVAR
    ========================= */
    public function toggleActivo(int $id): bool
    {
        try {
            $sql  = "UPDATE usuario SET activo = IF(activo = 1, 0, 1) WHERE id_usuario = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (\PDOException $e) {
            // La columna aún no existe, ejecuta: ALTER TABLE usuario ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
            return false;
        }
    }

    /* =========================
       ELIMINAR
    ========================= */
    public function eliminar(int $id): bool
    {
        $sql  = "DELETE FROM usuario WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }
}
