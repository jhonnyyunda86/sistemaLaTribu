<?php
/**
 * PedidoController
 * Gestiona la creación de pedidos (cliente y mesero),
 * cambio de estado y consulta de historial.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Reserva.php';

class PedidoController
{
    private Pedido  $pedidoModel;
    private Reserva $reservaModel;
    private PDO     $db;

    public function __construct()
    {
        $this->db           = (new Database())->conectar();
        $this->pedidoModel  = new Pedido($this->db);
        $this->reservaModel = new Reserva($this->db);
    }

    /* =========================
       OBTENER TODOS (admin)
    ========================= */
    public function obtenerTodos(): array
    {
        return $this->pedidoModel->obtenerTodos();
    }

    /* =========================
       OBTENER TODOS (mesero)
       Con cliente, estado, tipo y total
    ========================= */
    public function obtenerTodosMesero(): array
    {
        return $this->pedidoModel->obtenerTodosMesero();
    }

    /* =========================
       HISTORIAL DEL CLIENTE
    ========================= */
    public function historialCliente(int $idCliente): array
    {
        return $this->pedidoModel->historialCliente($idCliente);
    }

    /* =========================
       ESTADÍSTICAS DEL CLIENTE
    ========================= */
    public function statsCliente(int $idCliente): array
    {
        return $this->pedidoModel->statsCliente($idCliente);
    }

    /* =========================
       DETALLE DE UN PEDIDO (cliente)
       Valida que pertenezca al cliente
    ========================= */
    public function detallePedido(int $idPedido, int $idCliente): array
    {
        return $this->pedidoModel->detallePedido($idPedido, $idCliente);
    }

    /* =========================
       DETALLE DE UN PEDIDO (mesero/admin)
       Sin validar cliente
    ========================= */
    public function detallePedidoMesero(int $idPedido): array
    {
        return $this->pedidoModel->detallePedidoMesero($idPedido);
    }

    /* =========================
       CREAR PEDIDO (cliente — domicilio)
    ========================= */
    public function crearPedidoCliente(int $idUsuario, string $metodoPago, array $items): array
    {
        if (empty($items)) {
            return ['ok' => false, 'msg' => 'El carrito está vacío.'];
        }
        if (!$metodoPago) {
            return ['ok' => false, 'msg' => 'Selecciona un método de pago.'];
        }

        try {
            $this->db->beginTransaction();

            // Obtener o crear id_cliente
            $idCliente = $this->reservaModel->obtenerIdCliente($idUsuario);
            if (!$idCliente) {
                $idCliente = $this->reservaModel->crearCliente($idUsuario, '');
            }

            // Crear pedido (tipo 2 = Domicilio, estado 1 = Pendiente)
            $sql  = "INSERT INTO pedido (id_cliente, id_tipo_pedido, id_estado_pedido, fecha_pedido)
                     VALUES (:ic, 2, 1, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':ic' => $idCliente]);
            $idPedido = (int)$this->db->lastInsertId();

            // Insertar detalles validando stock
            foreach ($items as $item) {
                $idProd   = (int)($item['id']       ?? 0);
                $cantidad = (int)($item['cantidad']  ?? 0);
                $precio   = (float)($item['precio'] ?? 0);
                if ($idProd <= 0 || $cantidad <= 0) continue;

                // Verificar stock
                $sqlStk  = "SELECT COALESCE(cantidad_actual,0) FROM inventario WHERE id_producto = :id LIMIT 1";
                $stmtStk = $this->db->prepare($sqlStk);
                $stmtStk->execute([':id' => $idProd]);
                $stockDisp = (int)$stmtStk->fetchColumn();

                if ($stockDisp < $cantidad) {
                    throw new \Exception('Stock insuficiente para uno de los productos.');
                }

                $subtotal = $precio * $cantidad;
                $sqlDet   = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                             VALUES (:ip, :iprod, :cant, :pu, :sub)";
                $stmtDet  = $this->db->prepare($sqlDet);
                $stmtDet->execute([
                    ':ip' => $idPedido, ':iprod' => $idProd,
                    ':cant' => $cantidad, ':pu' => $precio, ':sub' => $subtotal,
                ]);
            }

            // Calcular total y crear factura
            $sqlTot  = "SELECT SUM(subtotal) FROM detalle_pedido WHERE id_pedido = :id";
            $stmtTot = $this->db->prepare($sqlTot);
            $stmtTot->execute([':id' => $idPedido]);
            $total = (float)$stmtTot->fetchColumn();

            $sqlFac  = "INSERT INTO factura (id_pedido, id_cliente, fecha, metodo_pago, total_factura)
                        VALUES (:ip, :ic, CURDATE(), :mp, :tot)";
            $stmtFac = $this->db->prepare($sqlFac);
            $stmtFac->execute([
                ':ip' => $idPedido, ':ic' => $idCliente,
                ':mp' => $metodoPago, ':tot' => $total,
            ]);

            $this->db->commit();
            return ['ok' => true, 'id_pedido' => $idPedido, 'msg' => "¡Pedido #{$idPedido} realizado con éxito!"];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /* =========================
       CREAR PEDIDO (mesero)
    ========================= */
    public function crearPedidoMesero(int $idUsuario, int $idTipo, array $items): array
    {
        $idMesero = $this->pedidoModel->obtenerIdMesero($idUsuario);
        if (!$idMesero) {
            return ['ok' => false, 'msg' => 'Tu usuario no tiene un registro de mesero.'];
        }
        if (empty($items)) {
            return ['ok' => false, 'msg' => 'El pedido está vacío.'];
        }

        return $this->pedidoModel->crearPedidoMesero($idMesero, $idTipo, $items, $this->db);
    }

    /* =========================
       CAMBIAR ESTADO
    ========================= */
    public function cambiarEstado(int $idPedido, int $idEstado): array
    {
        if ($idPedido <= 0 || $idEstado <= 0) {
            return ['ok' => false, 'msg' => 'Datos inválidos.'];
        }

        if (!$this->pedidoModel->cambiarEstado($idPedido, $idEstado)) {
            return ['ok' => false, 'msg' => 'Error al actualizar el estado.'];
        }

        return ['ok' => true, 'msg' => 'Estado del pedido actualizado.'];
    }
}
