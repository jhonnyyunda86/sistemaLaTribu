<?php

class Producto {

    private $conn;
    private $tabla = "producto";

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    /* =========================
       CONTAR PRODUCTOS
    ========================= */
    public function contar() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->tabla}");
        return (int)$stmt->fetchColumn();
    }

    /* =========================
       OBTENER TODOS
    ========================= */
    public function obtenerTodos() {
        $sql = "SELECT * FROM {$this->tabla} ORDER BY id_producto DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       CREAR PRODUCTO
    ========================= */
    public function crear($nombre, $precio, $descripcion, $imagen = '') {
        try {
            $this->conn->query("SELECT imagen FROM producto LIMIT 1");
            $tieneImagen = true;
        } catch (\PDOException $e) {
            $tieneImagen = false;
        }

        if ($tieneImagen) {
            $sql  = "INSERT INTO {$this->tabla} (nombre, precio, descripcion, imagen)
                     VALUES (:nombre, :precio, :descripcion, :imagen)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre',      $nombre);
            $stmt->bindParam(':precio',      $precio);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':imagen',      $imagen);
        } else {
            $sql  = "INSERT INTO {$this->tabla} (nombre, precio, descripcion)
                     VALUES (:nombre, :precio, :descripcion)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre',      $nombre);
            $stmt->bindParam(':precio',      $precio);
            $stmt->bindParam(':descripcion', $descripcion);
        }

        return $stmt->execute();
    }

    /* =========================
       ACTUALIZAR IMAGEN
    ========================= */
    public function actualizarImagen(int $id, string $nombreArchivo): bool
    {
        try {
            $this->conn->query("SELECT imagen FROM producto LIMIT 1");
        } catch (\PDOException $e) {
            return false;
        }
        $sql  = "UPDATE {$this->tabla} SET imagen = :imagen WHERE id_producto = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':imagen' => $nombreArchivo, ':id' => $id]);
    }

    /* =========================
       OBTENER POR ID
    ========================= */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE id_producto = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       ACTUALIZAR
    ========================= */
    public function actualizar($id, $nombre, $precio, $descripcion, $imagen = null) {
        // Detectar si la columna imagen existe
        try {
            $this->conn->query("SELECT imagen FROM producto LIMIT 1");
            $tieneImagen = true;
        } catch (\PDOException $e) {
            $tieneImagen = false;
        }

        if ($tieneImagen && $imagen !== null && $imagen !== '') {
            $sql = "UPDATE {$this->tabla}
                    SET nombre = :nombre, precio = :precio, descripcion = :descripcion, imagen = :imagen
                    WHERE id_producto = :id";
            $params = [':id' => $id, ':nombre' => $nombre, ':precio' => $precio, ':descripcion' => $descripcion, ':imagen' => $imagen];
        } else {
            $sql = "UPDATE {$this->tabla}
                    SET nombre = :nombre, precio = :precio, descripcion = :descripcion
                    WHERE id_producto = :id";
            $params = [':id' => $id, ':nombre' => $nombre, ':precio' => $precio, ':descripcion' => $descripcion];
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /* =========================
       MENÚ CLIENTE CON INVENTARIO
       Trae productos con categoría,
       stock e imagen para el panel cliente
    ========================= */
    public function obtenerMenuCliente(): array
    {
        // Detectar si la columna imagen existe
        try {
            $this->conn->query("SELECT imagen FROM producto LIMIT 1");
            $campoImagen = "p.imagen,";
        } catch (\PDOException $e) {
            $campoImagen = "NULL AS imagen,";
        }

        $sql = "SELECT
                    p.id_producto,
                    p.nombre,
                    p.precio,
                    p.descripcion,
                    {$campoImagen}
                    c.id_categoria,
                    c.nombre_categoria,
                    COALESCE(i.cantidad_actual, 0)  AS stock,
                    COALESCE(i.cantidad_minima, 5)  AS stock_minimo,
                    CASE
                        WHEN COALESCE(i.cantidad_actual, 0) = 0                                    THEN 'agotado'
                        WHEN COALESCE(i.cantidad_actual, 0) <= COALESCE(i.cantidad_minima, 5)      THEN 'bajo'
                        ELSE 'disponible'
                    END AS estado_stock
                FROM producto p
                LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventario         i ON p.id_producto  = i.id_producto
                ORDER BY c.nombre_categoria ASC, p.nombre ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       CATEGORÍAS CON CONTEO
    ========================= */
    public function obtenerCategorias(): array
    {
        $sql = "SELECT
                    c.id_categoria,
                    c.nombre_categoria,
                    COUNT(p.id_producto) AS total
                FROM categoria_producto c
                LEFT JOIN producto p ON p.id_categoria = c.id_categoria
                GROUP BY c.id_categoria
                ORDER BY c.nombre_categoria ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       ELIMINAR
    ========================= */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE id_producto = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>