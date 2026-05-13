<?php
/**
 * MesaController
 * Gestiona las operaciones CRUD de mesas
 * y la actualización de estado via AJAX.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Mesa.php';

class MesaController
{
    private Mesa $mesaModel;

    public function __construct()
    {
        $this->mesaModel = new Mesa((new Database())->conectar());
    }

    /* =========================
       OBTENER TODAS
    ========================= */
    public function obtenerTodas(): array
    {
        return $this->mesaModel->obtenerTodos();
    }

    /* =========================
       OBTENER POR ID
    ========================= */
    public function obtenerPorId(int $id): array|false
    {
        return $this->mesaModel->obtenerPorId($id);
    }

    /* =========================
       CREAR MESA
    ========================= */
    public function crear(array $datos): array
    {
        $numero    = trim($datos['numero_mesa'] ?? '');
        $capacidad = trim($datos['capacidad']   ?? '');
        $estado    = trim($datos['estado']      ?? 'disponible');

        if ($numero === '' || $capacidad === '') {
            return ['ok' => false, 'msg' => 'El número y la capacidad son obligatorios.'];
        }

        if (!$this->mesaModel->crear($numero, $capacidad, $estado)) {
            return ['ok' => false, 'msg' => 'Error al crear la mesa.'];
        }

        return ['ok' => true, 'msg' => 'Mesa creada correctamente.'];
    }

    /* =========================
       ACTUALIZAR ESTADO
       Usado también por el endpoint AJAX
    ========================= */
    public function actualizarEstado(int $id, string $estado): array
    {
        $estadosValidos = ['disponible', 'ocupada', 'reservada', 'mantenimiento'];

        if ($id <= 0 || !in_array($estado, $estadosValidos)) {
            return ['ok' => false, 'msg' => 'Datos inválidos.'];
        }

        if (!$this->mesaModel->actualizarEstado($id, $estado)) {
            return ['ok' => false, 'msg' => 'Error al actualizar el estado.'];
        }

        return ['ok' => true, 'msg' => 'Estado actualizado correctamente.'];
    }

    /* =========================
       ACTUALIZAR COMPLETO
    ========================= */
    public function actualizar(array $datos): array
    {
        $id        = (int)($datos['id_mesa']    ?? 0);
        $numero    = trim($datos['numero_mesa'] ?? '');
        $capacidad = trim($datos['capacidad']   ?? '');
        $estado    = trim($datos['estado']      ?? 'disponible');

        if ($id <= 0 || $numero === '' || $capacidad === '') {
            return ['ok' => false, 'msg' => 'Datos inválidos para actualizar.'];
        }

        if (!$this->mesaModel->actualizar($id, $numero, $capacidad, $estado)) {
            return ['ok' => false, 'msg' => 'Error al actualizar la mesa.'];
        }

        return ['ok' => true, 'msg' => 'Mesa actualizada correctamente.'];
    }

    /* =========================
       ELIMINAR
    ========================= */
    public function eliminar(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'ID inválido.'];
        }

        if (!$this->mesaModel->eliminar($id)) {
            return ['ok' => false, 'msg' => 'Error al eliminar la mesa.'];
        }

        return ['ok' => true, 'msg' => 'Mesa eliminada correctamente.'];
    }
}
