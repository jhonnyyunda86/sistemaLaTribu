<?php

class Inventario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ══════════════════════════════════════════
       ESTADÍSTICAS PARA CARDS
    ══════════════════════════════════════════ */
    public function stats(): array
    {
        // Total productos con inventario
        $total = (int)$this->db->query(
            "SELECT COUNT(*) FROM inventario"
        )->fetchColumn();

        // Agotados (cantidad_actual = 0)
        $agotados = (int)$this->db->query(
            "SELECT COUNT(*) FROM inventario WHERE cantidad_actual = 0"
        )->fetchColumn();

        // Stock bajo (0 < cantidad_actual <= cantidad_minima)
        $stockBajo = (int)$this->db->query(
            "SELECT COUNT(*) FROM inventario
             WHERE cantidad_actual > 0 AND cantidad_actual <= cantidad_minima"
        )->fetchColumn();

        // Entradas del día
        $entradasHoy = (int)$this->db->query(
            "SELECT COALESCE(SUM(cantidad),0) FROM movimiento_inventario
             WHERE tipo_movimiento = 'entrada' AND fecha_movimiento = CURDATE()"
        )->fetchColumn();

        return [
            'total'        => $total,
            'agotados'     => $agotados,
            'stock_bajo'   => $stockBajo,
            'entradas_hoy' => $entradasHoy,
        ];
    }

    /* ══════════════════════════════════════════
       LISTADO COMPLETO CON ESTADO
    ══════════════════════════════════════════ */
    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    i.id_inventario,
                    i.id_producto,
                    p.nombre            AS producto,
                    p.precio,
                    i.cantidad_actual,
                    i.cantidad_minima,
                    i.fecha_actualizacion,
                    CASE
                        WHEN i.cantidad_actual = 0                          THEN 'agotado'
                        WHEN i.cantidad_actual <= i.cantidad_minima         THEN 'bajo'
                        ELSE 'disponible'
                    END AS estado
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                ORDER BY estado ASC, p.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════
       OBTENER POR ID
    ══════════════════════════════════════════ */
    public function obtenerPorId(int $id): array|false
    {
        $sql  = "SELECT i.*, p.nombre AS producto
                 FROM inventario i
                 JOIN producto p ON i.id_producto = p.id_producto
                 WHERE i.id_inventario = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════
       OBTENER POR PRODUCTO
    ══════════════════════════════════════════ */
    public function obtenerPorProducto(int $idProducto): array|false
    {
        $sql  = "SELECT * FROM inventario WHERE id_producto = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idProducto]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════
       AGREGAR SUMINISTRO (ENTRADA)
    ══════════════════════════════════════════ */
    public function agregarSuministro(int $idInventario, int $cantidad, string $descripcion): bool
    {
        try {
            $this->db->beginTransaction();

            // Obtener id_producto
            $inv = $this->obtenerPorId($idInventario);
            if (!$inv) throw new \Exception('Inventario no encontrado');

            // Actualizar stock
            $sql  = "UPDATE inventario
                     SET cantidad_actual     = cantidad_actual + :cantidad,
                         fecha_actualizacion = CURDATE()
                     WHERE id_inventario = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':cantidad' => $cantidad, ':id' => $idInventario]);

            // Registrar movimiento
            $this->registrarMovimiento(
                (int)$inv['id_producto'],
                'entrada',
                $cantidad,
                $descripcion ?: 'Suministro manual'
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /* ══════════════════════════════════════════
       ACTUALIZAR STOCK MÍNIMO
    ══════════════════════════════════════════ */
    public function actualizarMinimo(int $idInventario, int $minimo): bool
    {
        $sql  = "UPDATE inventario SET cantidad_minima = :min WHERE id_inventario = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':min' => $minimo, ':id' => $idInventario]);
    }

    /* ══════════════════════════════════════════
       REGISTRAR MOVIMIENTO
    ══════════════════════════════════════════ */
    public function registrarMovimiento(int $idProducto, string $tipo, int $cantidad, string $descripcion = ''): bool
    {
        // Detectar si la columna descripcion existe
        try {
            $this->db->query("SELECT descripcion FROM movimiento_inventario LIMIT 1");
            $sql = "INSERT INTO movimiento_inventario
                        (id_producto, tipo_movimiento, cantidad, descripcion, fecha_movimiento)
                    VALUES (:id, :tipo, :cant, :desc, CURDATE())";
            $params = [':id' => $idProducto, ':tipo' => $tipo, ':cant' => $cantidad, ':desc' => $descripcion];
        } catch (\PDOException $e) {
            $sql = "INSERT INTO movimiento_inventario
                        (id_producto, tipo_movimiento, cantidad, fecha_movimiento)
                    VALUES (:id, :tipo, :cant, CURDATE())";
            $params = [':id' => $idProducto, ':tipo' => $tipo, ':cant' => $cantidad];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /* ══════════════════════════════════════════
       HISTORIAL DE MOVIMIENTOS
    ══════════════════════════════════════════ */
    public function historial(int $limite = 50, ?int $idProducto = null): array
    {
        // Detectar si la columna descripcion ya existe
        try {
            $this->db->query("SELECT descripcion FROM movimiento_inventario LIMIT 1");
            $campoDesc = "m.descripcion";
        } catch (\PDOException $e) {
            $campoDesc = "'' AS descripcion";
        }

        $where = $idProducto ? "WHERE m.id_producto = :id" : "";
        $sql   = "SELECT
                      m.id_movimiento,
                      m.tipo_movimiento,
                      m.cantidad,
                      {$campoDesc},
                      m.fecha_movimiento,
                      p.nombre AS producto
                  FROM movimiento_inventario m
                  JOIN producto p ON m.id_producto = p.id_producto
                  {$where}
                  ORDER BY m.id_movimiento DESC
                  LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        if ($idProducto) $stmt->bindValue(':id', $idProducto, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════
       VALIDAR STOCK SUFICIENTE
    ══════════════════════════════════════════ */
    public function hayStock(int $idProducto, int $cantidad): bool
    {
        $sql  = "SELECT cantidad_actual FROM inventario WHERE id_producto = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idProducto]);
        $stock = $stmt->fetchColumn();
        return $stock !== false && (int)$stock >= $cantidad;
    }

    /* ══════════════════════════════════════════
       DESCONTAR STOCK MANUALMENTE (sin trigger)
    ══════════════════════════════════════════ */
    public function descontarStock(int $idProducto, int $cantidad, string $descripcion = ''): bool
    {
        if (!$this->hayStock($idProducto, $cantidad)) return false;

        try {
            $this->db->beginTransaction();

            $sql  = "UPDATE inventario
                     SET cantidad_actual     = GREATEST(0, cantidad_actual - :cant),
                         fecha_actualizacion = CURDATE()
                     WHERE id_producto = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':cant' => $cantidad, ':id' => $idProducto]);

            $this->registrarMovimiento($idProducto, 'salida', $cantidad, $descripcion);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /* ══════════════════════════════════════════
       CONTAR (para dashboard card)
    ══════════════════════════════════════════ */
    public function contar(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM inventario")->fetchColumn();
    }
}
