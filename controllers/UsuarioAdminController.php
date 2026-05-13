<?php
/**
 * UsuarioAdminController
 * Gestiona el CRUD completo de usuarios desde el
 * panel del administrador (todos los roles).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioAdmin.php';

class UsuarioAdminController
{
    private UsuarioAdmin $model;

    public function __construct()
    {
        $this->model = new UsuarioAdmin((new Database())->conectar());
    }

    /* =========================
       OBTENER TODOS
    ========================= */
    public function obtenerTodos(): array
    {
        return $this->model->obtenerTodos();
    }

    /* =========================
       OBTENER POR ROL
    ========================= */
    public function obtenerPorRol(string $rol): array
    {
        return $this->model->obtenerPorRol($rol);
    }

    /* =========================
       CONTAR POR ROL (KPI cards)
    ========================= */
    public function contarPorRol(string $rol): int
    {
        return $this->model->contarPorRol($rol);
    }

    /* =========================
       CREAR USUARIO
    ========================= */
    public function crear(array $datos): array
    {
        $nombre   = trim($datos['nombre']   ?? '');
        $correo   = trim($datos['correo']   ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        $rol      = trim($datos['rol']      ?? '');
        $password = $datos['password']      ?? '';
        $confirm  = $datos['confirm']       ?? '';

        if (!$nombre || !$correo || !$telefono || !$rol || !$password) {
            return ['ok' => false, 'msg' => 'Todos los campos son obligatorios.'];
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'El correo electrónico no es válido.'];
        }
        if ($password !== $confirm) {
            return ['ok' => false, 'msg' => 'Las contraseñas no coinciden.'];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres.'];
        }
        if ($this->model->existeCorreo($correo)) {
            return ['ok' => false, 'msg' => 'Este correo ya está registrado.'];
        }

        $ok = $this->model->crear([
            'nombre'   => $nombre,
            'correo'   => $correo,
            'telefono' => $telefono,
            'role'     => $rol,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return $ok
            ? ['ok' => true,  'msg' => "Usuario «{$nombre}» creado correctamente."]
            : ['ok' => false, 'msg' => 'No se pudo crear el usuario.'];
    }

    /* =========================
       EDITAR USUARIO
    ========================= */
    public function editar(array $datos): array
    {
        $id       = (int)($datos['id_usuario'] ?? 0);
        $nombre   = trim($datos['nombre']      ?? '');
        $correo   = trim($datos['correo']      ?? '');
        $telefono = trim($datos['telefono']    ?? '');
        $rol      = trim($datos['rol']         ?? '');

        if (!$id || !$nombre || !$correo || !$rol) {
            return ['ok' => false, 'msg' => 'Datos inválidos.'];
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'El correo no es válido.'];
        }
        if ($this->model->existeCorreo($correo, $id)) {
            return ['ok' => false, 'msg' => 'Ese correo ya lo usa otro usuario.'];
        }

        $ok = $this->model->actualizar($id, [
            'nombre'   => $nombre,
            'correo'   => $correo,
            'telefono' => $telefono,
            'role'     => $rol,
        ]);

        // Cambiar contraseña si se proporcionó
        if ($ok && !empty($datos['password'])) {
            $np = $datos['password'];
            $cp = $datos['confirm'] ?? '';
            if ($np !== $cp)        return ['ok' => false, 'msg' => 'Las contraseñas no coinciden.'];
            if (strlen($np) < 6)    return ['ok' => false, 'msg' => 'Mínimo 6 caracteres.'];
            $this->model->actualizarPassword($id, $np);
        }

        return $ok
            ? ['ok' => true,  'msg' => 'Usuario actualizado correctamente.']
            : ['ok' => false, 'msg' => 'Error al actualizar.'];
    }

    /* =========================
       ACTIVAR / DESACTIVAR
    ========================= */
    public function toggleActivo(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'ID inválido.'];
        }

        return $this->model->toggleActivo($id)
            ? ['ok' => true,  'msg' => 'Estado del usuario actualizado.']
            : ['ok' => false, 'msg' => 'Error al cambiar el estado.'];
    }

    /* =========================
       ELIMINAR
    ========================= */
    public function eliminar(int $id, int $sesionId): array
    {
        if ($id === $sesionId) {
            return ['ok' => false, 'msg' => 'No puedes eliminar tu propia cuenta.'];
        }
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'ID inválido.'];
        }

        return $this->model->eliminar($id)
            ? ['ok' => true,  'msg' => 'Usuario eliminado correctamente.']
            : ['ok' => false, 'msg' => 'Error al eliminar.'];
    }
}
