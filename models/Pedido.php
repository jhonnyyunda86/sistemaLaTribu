<?php

class Pedido
{
    private $conn;
    private $tabla = "pedido";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* =========================
       OBTENER ID_MESERO por id_usuario
    ========================= */
    public function obtenerIdMesero(int $idUsuario): int|false
    {
        $sql  = "SELECT id_mesero FROM mesero WHERE id_usuario = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idUsuario]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_mesero'] : false;
    }

    /* =========================
       TODOS LOS PEDIDOS (vista mesero)
       Con cliente, estado, tipo y total
    ========================= */
    public function obtenerTodosMesero(): array
    {
        $sql = "SELECT
                    p.id_pedido,
                    p.fecha_pedido,
                    p.id_mesero,
                    ep.nombre_estado                AS estado,
                    tp.nombre_tipo                  AS tipo,
                    COALESCE(SUM(dp.subtotal), 0)   AS total,
                    COUNT(dp.id_detalle)            AS num_productos,
                    u.nombre                        AS cliente,
                    MAX(f.metodo_pago)              AS metodo_pago
                FROM {$this->tabla} p
                LEFT JOIN estado_pedido  ep ON p.id_estado_pedido = ep.id_estado_pedido
                LEFT JOIN tipo_pedido    tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
                LEFT JOIN detalle_pedido dp ON p.id_pedido        = dp.id_pedido
                LEFT JOIN cliente         c ON p.id_cliente       = c.id_cliente
                LEFT JOIN usuario         u ON c.id_usuario       = u.id_usuario
                LEFT JOIN factura         f ON p.id_pedido        = f.id_pedido
                GROUP BY p.id_pedido, p.fecha_pedido, p.id_mesero,
                         ep.nombre_estado, tp.nombre_tipo, u.nombre
                ORDER BY
                    FIELD(ep.nombre_estado,'Pendiente','En preparación','Entregado','Cancelado'),
                    p.fecha_pedido DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       DETALLE PEDIDO (sin validar cliente)
       Para uso del mesero
    ========================= */
    public function detallePedidoMesero(int $idPedido): array
    {
        $sqlCab = "SELECT
                       p.id_pedido,
                       p.fecha_pedido,
                       p.id_mesero,
                       ep.nombre_estado  AS estado,
                       tp.nombre_tipo    AS tipo,
                       u.nombre          AS cliente,
                       f.metodo_pago,
                       COALESCE(f.total_factura, 0) AS total_factura
                   FROM {$this->tabla} p
                   LEFT JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
                   LEFT JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
                   LEFT JOIN cliente        c ON p.id_cliente       = c.id_cliente
                   LEFT JOIN usuario        u ON c.id_usuario       = u.id_usuario
                   LEFT JOIN factura        f ON p.id_pedido        = f.id_pedido
                   WHERE p.id_pedido = :pid LIMIT 1";

        $stmtCab = $this->conn->prepare($sqlCab);
        $stmtCab->execute([':pid' => $idPedido]);
        $cabecera = $stmtCab->fetch(PDO::FETCH_ASSOC);
        if (!$cabecera) return [];

        $sqlProd = "SELECT pr.nombre, dp.cantidad, dp.precio_unitario, dp.subtotal
                    FROM detalle_pedido dp
                    JOIN producto pr ON dp.id_producto = pr.id_producto
                    WHERE dp.id_pedido = :pid ORDER BY pr.nombre ASC";
        $stmtProd = $this->conn->prepare($sqlProd);
        $stmtProd->execute([':pid' => $idPedido]);
        $cabecera['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        return $cabecera;
    }

    /* =========================
       CAMBIAR ESTADO DE PEDIDO
    ========================= */
    public function cambiarEstado(int $idPedido, int $idEstado): bool
    {
        $sql  = "UPDATE {$this->tabla} SET id_estado_pedido = :estado WHERE id_pedido = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':estado' => $idEstado, ':id' => $idPedido]);
    }

    /* =========================
       CREAR PEDIDO (desde mesero)
    ========================= */
    public function crearPedidoMesero(int $idMesero, int $idTipo, array $items, PDO $db): array
    {
        try {
            $db->beginTransaction();

            // Crear pedido (estado 1 = Pendiente)
            $sql  = "INSERT INTO {$this->tabla} (id_mesero, id_tipo_pedido, id_estado_pedido, fecha_pedido)
                     VALUES (:im, :it, 1, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([':im' => $idMesero, ':it' => $idTipo]);
            $idPedido = (int)$db->lastInsertId();

            $total = 0;
            foreach ($items as $item) {
                $idProd   = (int)($item['id']       ?? 0);
                $cantidad = (int)($item['cantidad']  ?? 0);
                $precio   = (float)($item['precio'] ?? 0);
                if ($idProd <= 0 || $cantidad <= 0) continue;

                $subtotal = $precio * $cantidad;
                $total   += $subtotal;

                $sqlD = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                         VALUES (:ip, :iprod, :cant, :pu, :sub)";
                $stmtD = $db->prepare($sqlD);
                $stmtD->execute([
                    ':ip'    => $idPedido,
                    ':iprod' => $idProd,
                    ':cant'  => $cantidad,
                    ':pu'    => $precio,
                    ':sub'   => $subtotal,
                ]);
            }

            $db->commit();
            return ['ok' => true, 'id_pedido' => $idPedido, 'total' => $total];

        } catch (\Exception $e) {
            $db->rollBack();
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /* =========================
       CONTAR TOTAL
    ========================= */
    public function contar(): int
    {
        return (int)$this->conn->query("SELECT COUNT(*) FROM {$this->tabla}")->fetchColumn();
    }

    /* =========================
       OBTENER TODOS (admin)
    ========================= */
    public function obtenerTodos(): array
    {
        $stmt = $this->conn->query(
            "SELECT * FROM {$this->tabla} ORDER BY id_pedido DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       HISTORIAL POR CLIENTE
       Devuelve pedidos con estado,
       tipo, total y método de pago
    ========================= */
    public function historialCliente(int $idCliente): array
    {
        $sql = "SELECT
                    p.id_pedido,
                    p.fecha_pedido,
                    ep.nombre_estado                    AS estado,
                    tp.nombre_tipo                      AS tipo,
                    COALESCE(SUM(dp.subtotal), 0)       AS total,
                    MAX(f.metodo_pago)                  AS metodo_pago,
                    MAX(f.id_factura)                   AS id_factura,
                    COUNT(dp.id_detalle)                AS num_productos
                FROM {$this->tabla} p
                LEFT JOIN estado_pedido  ep ON p.id_estado_pedido = ep.id_estado_pedido
                LEFT JOIN tipo_pedido    tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
                LEFT JOIN detalle_pedido dp ON p.id_pedido        = dp.id_pedido
                LEFT JOIN factura         f ON p.id_pedido        = f.id_pedido
                WHERE p.id_cliente = :id
                GROUP BY p.id_pedido, p.fecha_pedido, ep.nombre_estado, tp.nombre_tipo
                ORDER BY p.fecha_pedido DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idCliente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       DETALLE DE UN PEDIDO
       Valida que pertenezca al cliente
    ========================= */
    public function detallePedido(int $idPedido, int $idCliente): array
    {
        // Cabecera
        $sqlCab = "SELECT
                       p.id_pedido,
                       p.fecha_pedido,
                       ep.nombre_estado  AS estado,
                       tp.nombre_tipo    AS tipo,
                       f.metodo_pago,
                       f.total_factura,
                       f.id_factura
                   FROM {$this->tabla} p
                   LEFT JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
                   LEFT JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
                   LEFT JOIN factura        f ON p.id_pedido        = f.id_pedido
                   WHERE p.id_pedido = :pid AND p.id_cliente = :cid
                   LIMIT 1";

        $stmtCab = $this->conn->prepare($sqlCab);
        $stmtCab->execute([':pid' => $idPedido, ':cid' => $idCliente]);
        $cabecera = $stmtCab->fetch(PDO::FETCH_ASSOC);

        if (!$cabecera) return [];

        // Productos
        $sqlProd = "SELECT
                        pr.nombre,
                        dp.cantidad,
                        dp.precio_unitario,
                        dp.subtotal
                    FROM detalle_pedido dp
                    JOIN producto pr ON dp.id_producto = pr.id_producto
                    WHERE dp.id_pedido = :pid
                    ORDER BY pr.nombre ASC";

        $stmtProd = $this->conn->prepare($sqlProd);
        $stmtProd->execute([':pid' => $idPedido]);
        $cabecera['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        return $cabecera;
    }

    /* =========================
       ESTADÍSTICAS DEL CLIENTE
    ========================= */
    public function statsCliente(int $idCliente): array
    {
        $sql = "SELECT
                    COUNT(DISTINCT p.id_pedido)          AS total_pedidos,
                    COALESCE(SUM(dp.subtotal), 0)        AS total_gastado,
                    COALESCE(MAX(dp.subtotal_sum), 0)    AS pedido_mayor
                FROM {$this->tabla} p
                LEFT JOIN (
                    SELECT id_pedido, SUM(subtotal) AS subtotal_sum, SUM(subtotal) AS subtotal
                    FROM detalle_pedido GROUP BY id_pedido
                ) dp ON p.id_pedido = dp.id_pedido
                WHERE p.id_cliente = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idCliente]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Pedido más reciente
        $sqlRec = "SELECT fecha_pedido FROM {$this->tabla}
                   WHERE id_cliente = :id ORDER BY fecha_pedido DESC LIMIT 1";
        $stmtRec = $this->conn->prepare($sqlRec);
        $stmtRec->execute([':id' => $idCliente]);
        $row['ultimo_pedido'] = $stmtRec->fetchColumn() ?: null;

        return $row;
    }
}
