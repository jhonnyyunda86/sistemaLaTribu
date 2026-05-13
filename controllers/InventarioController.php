<?php
/**
 * InventarioController
 * Gestiona el stock de productos: suministros,
 * stock mínimo, historial de movimientos y registro
 * de productos sin inventario.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Inventario.php';

class InventarioController
{
    private Inventario $invModel;
    private PDO        $db;

    public function __construct()
    {
        $this->db       = (new Database())->conectar();
        $this->invModel = new Inventario($this->db);
    }

    /* =========================
       ESTADÍSTICAS (KPI cards)
    ========================= */
    public function stats(): array
    {
        return $this->invModel->stats();
    }

    /* =========================
       OBTENER TODOS CON ESTADO
    ========================= */
    public function obtenerTodos(): array
    {
        return $this->invModel->obtenerTodos();
    }

    /* =========================
       AGREGAR SUMINISTRO
    ========================= */
    public function agregarSuministro(array $datos): array
    {
        $idInv       = (int)($datos['id_inventario'] ?? 0);
        $cantidad    = (int)($datos['cantidad']       ?? 0);
        $descripcion = trim($datos['descripcion']     ?? '');

        if ($idInv <= 0 || $cantidad <= 0) {
            return ['ok' => false, 'msg' => 'Datos inválidos. La cantidad debe ser mayor a 0.'];
        }

        if (!$this->invModel->agregarSuministro($idInv, $cantidad, $descripcion)) {
            return ['ok' => false, 'msg' => 'Error al registrar el suministro.'];
        }

        return ['ok' => true, 'msg' => 'Suministro agregado correctamente. Stock actualizado.'];
    }

    /* =========================
       ACTUALIZAR STOCK MÍNIMO
    ========================= */
    public function actualizarMinimo(array $datos): array
    {
        $idInv  = (int)($datos['id_inventario']  ?? 0);
        $minimo = (int)($datos['cantidad_minima'] ?? 0);

        if ($idInv <= 0 || $minimo < 0) {
            return ['ok' => false, 'msg' => 'Datos inválidos.'];
        }

        if (!$this->invModel->actualizarMinimo($idInv, $minimo)) {
            return ['ok' => false, 'msg' => 'Error al actualizar el stock mínimo.'];
        }

        return ['ok' => true, 'msg' => 'Stock mínimo actualizado.'];
    }

    /* =========================
       REGISTRAR PRODUCTO SIN INVENTARIO
    ========================= */
    public function registrarProducto(array $datos): array
    {
        $idProd       = (int)($datos['id_producto']    ?? 0);
        $stockInicial = (int)($datos['stock_inicial']  ?? 0);
        $stockMinimo  = (int)($datos['stock_minimo']   ?? 5);

        if ($idProd <= 0) {
            return ['ok' => false, 'msg' => 'Selecciona un producto válido.'];
        }

        // Verificar que no tenga ya inventario
        $sql  = "SELECT COUNT(*) FROM inventario WHERE id_producto = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idProd]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['ok' => false, 'msg' => 'Este producto ya tiene registro en inventario.'];
        }

        $sqlIns = "INSERT INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion)
                   VALUES (:id, :stock, :minimo, CURDATE())";
        $stmtIns = $this->db->prepare($sqlIns);
        $ok = $stmtIns->execute([':id' => $idProd, ':stock' => $stockInicial, ':minimo' => $stockMinimo]);

        if ($ok && $stockInicial > 0) {
            $this->invModel->registrarMovimiento($idProd, 'entrada', $stockInicial, 'Stock inicial registrado');
        }

        return $ok
            ? ['ok' => true,  'msg' => 'Producto registrado en inventario correctamente.']
            : ['ok' => false, 'msg' => 'Error al registrar.'];
    }

    /* =========================
       HISTORIAL DE MOVIMIENTOS
    ========================= */
    public function historial(int $limite = 30, ?int $idProducto = null): array
    {
        return $this->invModel->historial($limite, $idProducto);
    }

    /* =========================
       VERIFICAR STOCK
    ========================= */
    public function hayStock(int $idProducto, int $cantidad): bool
    {
        return $this->invModel->hayStock($idProducto, $cantidad);
    }
}
