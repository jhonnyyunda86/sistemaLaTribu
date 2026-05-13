<?php
/**
 * ProductoController
 * Gestiona las operaciones CRUD de productos del menú
 * y la integración con el inventario al crear.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Inventario.php';

class ProductoController
{
    private Producto   $productoModel;
    private Inventario $invModel;
    private PDO        $db;

    public function __construct()
    {
        $this->db            = (new Database())->conectar();
        $this->productoModel = new Producto($this->db);
        $this->invModel      = new Inventario($this->db);
    }

    /* =========================
       OBTENER TODOS
    ========================= */
    public function obtenerTodos(): array
    {
        return $this->productoModel->obtenerTodos();
    }

    /* =========================
       OBTENER MENÚ CLIENTE
       Con stock e imagen
    ========================= */
    public function obtenerMenuCliente(): array
    {
        return $this->productoModel->obtenerMenuCliente();
    }

    /* =========================
       OBTENER CATEGORÍAS
    ========================= */
    public function obtenerCategorias(): array
    {
        return $this->productoModel->obtenerCategorias();
    }

    /* =========================
       CREAR PRODUCTO
       Incluye subida de imagen e
       inicialización de inventario
    ========================= */
    public function crear(array $datos, array $archivo = []): array
    {
        $nombre       = trim($datos['nombre']        ?? '');
        $precio       = trim($datos['precio']        ?? '');
        $descripcion  = trim($datos['descripcion']   ?? '');
        $stockInicial = (int)($datos['stock_inicial'] ?? 0);
        $stockMinimo  = (int)($datos['stock_minimo']  ?? 5);
        $imagen       = '';

        if ($nombre === '' || $precio === '') {
            return ['ok' => false, 'msg' => 'El nombre y el precio son obligatorios.'];
        }

        // Procesar imagen si se subió
        if (!empty($archivo['name'])) {
            $resultado = $this->subirImagen($archivo);
            if ($resultado === false) {
                return ['ok' => false, 'msg' => 'Imagen inválida. Usa JPG, PNG o WEBP de máximo 2 MB.'];
            }
            $imagen = $resultado;
        }

        if (!$this->productoModel->crear($nombre, $precio, $descripcion, $imagen)) {
            return ['ok' => false, 'msg' => 'Error al agregar el producto.'];
        }

        $nuevoId = (int)$this->db->lastInsertId();

        // Crear registro en inventario
        $sql = "INSERT INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion)
                VALUES (:id, :stock, :minimo, CURDATE())
                ON DUPLICATE KEY UPDATE
                    cantidad_actual     = :stock2,
                    cantidad_minima     = :minimo2,
                    fecha_actualizacion = CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'     => $nuevoId, ':stock'  => $stockInicial,
            ':minimo' => $stockMinimo, ':stock2' => $stockInicial, ':minimo2' => $stockMinimo,
        ]);

        if ($stockInicial > 0) {
            $this->invModel->registrarMovimiento($nuevoId, 'entrada', $stockInicial, 'Stock inicial al crear producto');
        }

        return ['ok' => true, 'msg' => "Producto agregado correctamente con stock inicial de {$stockInicial} unidades.", 'id' => $nuevoId];
    }

    /* =========================
       ACTUALIZAR PRODUCTO
    ========================= */
    public function actualizar(array $datos, array $archivo = []): array
    {
        $id          = (int)($datos['id_producto']  ?? 0);
        $nombre      = trim($datos['nombre']        ?? '');
        $precio      = trim($datos['precio']        ?? '');
        $descripcion = trim($datos['descripcion']   ?? '');

        if ($id <= 0 || $nombre === '' || $precio === '') {
            return ['ok' => false, 'msg' => 'Datos inválidos para actualizar.'];
        }

        // Subir nueva imagen si se proporcionó
        if (!empty($archivo['name']) && $archivo['error'] === UPLOAD_ERR_OK) {
            $resultado = $this->subirImagen($archivo);
            if ($resultado === false) {
                return ['ok' => false, 'msg' => 'Imagen inválida. Usa JPG, PNG o WEBP de máximo 2 MB.'];
            }
            // Borrar imagen anterior
            $prodActual = $this->productoModel->obtenerPorId($id);
            if (!empty($prodActual['imagen'])) {
                $rutaAnterior = __DIR__ . '/../img/productos/' . $prodActual['imagen'];
                if (file_exists($rutaAnterior)) @unlink($rutaAnterior);
            }
            $this->productoModel->actualizarImagen($id, $resultado);
        }

        if (!$this->productoModel->actualizar($id, $nombre, $precio, $descripcion)) {
            return ['ok' => false, 'msg' => 'Error al actualizar el producto.'];
        }

        return ['ok' => true, 'msg' => 'Producto actualizado correctamente.'];
    }

    /* =========================
       ELIMINAR PRODUCTO
    ========================= */
    public function eliminar(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'ID inválido.'];
        }

        // Borrar imagen si existe
        $prod = $this->productoModel->obtenerPorId($id);
        if ($prod && !empty($prod['imagen'])) {
            $ruta = __DIR__ . '/../img/productos/' . $prod['imagen'];
            if (file_exists($ruta)) @unlink($ruta);
        }

        if (!$this->productoModel->eliminar($id)) {
            return ['ok' => false, 'msg' => 'Error al eliminar el producto.'];
        }

        return ['ok' => true, 'msg' => 'Producto eliminado correctamente.'];
    }

    /* =========================
       HELPER: SUBIR IMAGEN
    ========================= */
    private function subirImagen(array $file): string|false
    {
        $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxBytes   = 2 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK)          return false;
        if (!in_array($file['type'], $permitidos))      return false;
        if ($file['size'] > $maxBytes)                  return false;

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nombre  = 'prod_' . uniqid() . '.' . $ext;
        $destino = __DIR__ . '/../img/productos/' . $nombre;

        return move_uploaded_file($file['tmp_name'], $destino) ? $nombre : false;
    }
}
