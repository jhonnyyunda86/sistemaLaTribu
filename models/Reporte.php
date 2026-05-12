<?php

class Reporte
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ══════════════════════════════════════════════
       VENTAS (facturas) por rango de fechas
       ══════════════════════════════════════════════ */
    public function ventasPorRango(string $desde, string $hasta): array
    {
        $sql = "SELECT
                    f.id_factura,
                    f.fecha,
                    f.metodo_pago,
                    f.total_factura,
                    u.nombre   AS cliente,
                    p.id_pedido
                FROM factura f
                LEFT JOIN cliente  c ON f.id_cliente  = c.id_cliente
                LEFT JOIN usuario  u ON c.id_usuario  = u.id_usuario
                LEFT JOIN pedido   p ON f.id_pedido   = p.id_pedido
                WHERE f.fecha BETWEEN :desde AND :hasta
                ORDER BY f.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════
       TOTAL VENTAS por rango
       ══════════════════════════════════════════════ */
    public function totalVentas(string $desde, string $hasta): float
    {
        $sql  = "SELECT COALESCE(SUM(total_factura), 0)
                 FROM factura
                 WHERE fecha BETWEEN :desde AND :hasta";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return (float)$stmt->fetchColumn();
    }

    /* ══════════════════════════════════════════════
       PEDIDOS por rango de fechas
       ══════════════════════════════════════════════ */
    public function pedidosPorRango(string $desde, string $hasta): array
    {
        $sql = "SELECT
                    p.id_pedido,
                    p.fecha_pedido,
                    ep.nombre_estado AS estado,
                    tp.nombre_tipo   AS tipo,
                    u.nombre         AS cliente,
                    COALESCE(SUM(dp.subtotal), 0) AS total
                FROM pedido p
                LEFT JOIN estado_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
                LEFT JOIN tipo_pedido   tp ON p.id_tipo_pedido   = tp.id_tipo_pedido
                LEFT JOIN cliente        c ON p.id_cliente        = c.id_cliente
                LEFT JOIN usuario        u ON c.id_usuario        = u.id_usuario
                LEFT JOIN detalle_pedido dp ON p.id_pedido        = dp.id_pedido
                WHERE DATE(p.fecha_pedido) BETWEEN :desde AND :hasta
                GROUP BY p.id_pedido
                ORDER BY p.fecha_pedido DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════
       RESERVAS por rango de fechas
       ══════════════════════════════════════════════ */
    public function reservasPorRango(string $desde, string $hasta): array
    {
        $sql = "SELECT
                    r.id_reserva,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.numero_personas,
                    m.numero_mesa,
                    er.nombre_estado AS estado,
                    u.nombre         AS cliente
                FROM reserva r
                LEFT JOIN mesa          m  ON r.id_mesa             = m.id_mesa
                LEFT JOIN estado_reserva er ON r.id_estado_reserva  = er.id_estado_reserva
                LEFT JOIN cliente        c  ON r.id_cliente          = c.id_cliente
                LEFT JOIN usuario        u  ON c.id_usuario          = u.id_usuario
                WHERE r.fecha_reserva BETWEEN :desde AND :hasta
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════
       PRODUCTOS MÁS VENDIDOS por rango
       ══════════════════════════════════════════════ */
    public function productosMasVendidos(string $desde, string $hasta, int $limite = 5): array
    {
        $sql = "SELECT
                    pr.nombre,
                    SUM(dp.cantidad)  AS total_unidades,
                    SUM(dp.subtotal)  AS total_ingresos
                FROM detalle_pedido dp
                JOIN producto pr ON dp.id_producto = pr.id_producto
                JOIN pedido    p  ON dp.id_pedido   = p.id_pedido
                WHERE DATE(p.fecha_pedido) BETWEEN :desde AND :hasta
                GROUP BY pr.id_producto
                ORDER BY total_unidades DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':desde',  $desde);
        $stmt->bindValue(':hasta',  $hasta);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════
       VENTAS AGRUPADAS POR DÍA (para gráfica)
       ══════════════════════════════════════════════ */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        $sql = "SELECT
                    fecha,
                    COUNT(*)              AS num_facturas,
                    SUM(total_factura)    AS total
                FROM factura
                WHERE fecha BETWEEN :desde AND :hasta
                GROUP BY fecha
                ORDER BY fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════
       RESUMEN GENERAL por rango
       ══════════════════════════════════════════════ */
    public function resumen(string $desde, string $hasta): array
    {
        // Total ventas
        $totalVentas = $this->totalVentas($desde, $hasta);

        // Num pedidos
        $sql  = "SELECT COUNT(*) FROM pedido WHERE DATE(fecha_pedido) BETWEEN :d AND :h";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':d' => $desde, ':h' => $hasta]);
        $numPedidos = (int)$stmt->fetchColumn();

        // Num reservas
        $sql  = "SELECT COUNT(*) FROM reserva WHERE fecha_reserva BETWEEN :d AND :h";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':d' => $desde, ':h' => $hasta]);
        $numReservas = (int)$stmt->fetchColumn();

        // Num facturas
        $sql  = "SELECT COUNT(*) FROM factura WHERE fecha BETWEEN :d AND :h";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':d' => $desde, ':h' => $hasta]);
        $numFacturas = (int)$stmt->fetchColumn();

        // Ticket promedio
        $ticketPromedio = $numFacturas > 0 ? $totalVentas / $numFacturas : 0;

        return [
            'total_ventas'    => $totalVentas,
            'num_pedidos'     => $numPedidos,
            'num_reservas'    => $numReservas,
            'num_facturas'    => $numFacturas,
            'ticket_promedio' => $ticketPromedio,
        ];
    }
}
