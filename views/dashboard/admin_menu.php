<?php
session_start();
if (!isset($_SESSION['usuario'])) { 
    header('Location: ../usuarios/login.php'); 
    exit; 
}

require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../models/Producto.php';
require_once __DIR__.'/../../models/Inventario.php';

$db            = (new Database())->conectar();
$productoModel = new Producto($db);
$invModel      = new Inventario($db);

$mensaje = '';
$error   = '';

/* =========================
   HELPER: SUBIR IMAGEN
========================= */
function subirImagenProducto(array $file): string|false
{
    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxBytes   = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK)          return false;
    if (!in_array($file['type'], $permitidos))      return false;
    if ($file['size'] > $maxBytes)                  return false;

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nombre   = 'prod_' . uniqid() . '.' . $ext;
    $destino  = __DIR__ . '/../../img/productos/' . $nombre;

    return move_uploaded_file($file['tmp_name'], $destino) ? $nombre : false;
}

/* =========================
   CREAR PRODUCTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $nombre       = trim($_POST['nombre']         ?? '');
    $precio       = trim($_POST['precio']         ?? '');
    $descripcion  = trim($_POST['descripcion']    ?? '');
    $stockInicial = (int)($_POST['stock_inicial'] ?? 0);
    $stockMinimo  = (int)($_POST['stock_minimo']  ?? 5);
    $imagen       = '';

    // Procesar imagen si se subió
    if (!empty($_FILES['imagen']['name'])) {
        $resultado = subirImagenProducto($_FILES['imagen']);
        if ($resultado === false) {
            $error = "Imagen inválida. Usa JPG, PNG o WEBP de máximo 2 MB.";
        } else {
            $imagen = $resultado;
        }
    }

    if (!$error) {
        if ($nombre === '' || $precio === '') {
            $error = "El nombre y el precio son obligatorios.";
        } elseif ($productoModel->crear($nombre, $precio, $descripcion, $imagen)) {
            $nuevoId = (int)$db->lastInsertId();

            $sqlInv = "INSERT INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion)
                       VALUES (:id, :stock, :minimo, CURDATE())
                       ON DUPLICATE KEY UPDATE
                           cantidad_actual     = :stock2,
                           cantidad_minima     = :minimo2,
                           fecha_actualizacion = CURDATE()";
            $stmtInv = $db->prepare($sqlInv);
            $stmtInv->execute([
                ':id'     => $nuevoId, ':stock'  => $stockInicial,
                ':minimo' => $stockMinimo, ':stock2' => $stockInicial, ':minimo2' => $stockMinimo,
            ]);

            if ($stockInicial > 0) {
                $invModel->registrarMovimiento($nuevoId, 'entrada', $stockInicial, 'Stock inicial al crear producto');
            }

            $mensaje = "Producto agregado correctamente.";
        } else {
            $error = "Error al agregar el producto.";
        }
    }
}

/* =========================
   ACTUALIZAR PRODUCTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    $id          = (int)($_POST['id_producto']  ?? 0);
    $nombre      = trim($_POST['nombre']        ?? '');
    $precio      = trim($_POST['precio']        ?? '');
    $descripcion = trim($_POST['descripcion']   ?? '');
    $imagenNueva = null;

    if ($id <= 0 || $nombre === '' || $precio === '') {
        $error = "Datos inválidos para actualizar.";
    } else {
        // Procesar imagen si se subió una nueva
        if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $resultado = subirImagenProducto($_FILES['imagen']);
            if ($resultado === false) {
                $error = "Imagen inválida. Usa JPG, PNG o WEBP de máximo 2 MB.";
            } else {
                // Borrar imagen anterior
                $prodActual = $productoModel->obtenerPorId($id);
                if (!empty($prodActual['imagen'])) {
                    $rutaAnterior = __DIR__ . '/../../img/productos/' . $prodActual['imagen'];
                    if (file_exists($rutaAnterior)) @unlink($rutaAnterior);
                }
                $imagenNueva = $resultado;
            }
        }

        if (!$error) {
            // Actualizar datos básicos
            $ok = $productoModel->actualizar($id, $nombre, $precio, $descripcion);
            // Actualizar imagen solo si se subió una nueva
            if ($ok && $imagenNueva !== null) {
                $productoModel->actualizarImagen($id, $imagenNueva);
            }
            $mensaje = $ok ? "Producto actualizado correctamente." : "Error al actualizar el producto.";
            if (!$ok) $error = "Error al actualizar el producto.";
        }
    }
}

/* =========================
   ELIMINAR PRODUCTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto'])) {
    $id = (int)($_POST['id_producto'] ?? 0);

    if ($id > 0 && $productoModel->eliminar($id)) {
        $mensaje = "Producto eliminado correctamente.";
    } else {
        $error = "Error al eliminar el producto.";
    }
}

$productos = $productoModel->obtenerTodos();

$titulo = 'Menú del restaurante';

require_once __DIR__.'/../layouts/header.php';
require_once __DIR__.'/../layouts/sidebar.php';
?>

<div class="bg-white rounded-2xl shadow p-6">

    <!-- MENSAJES -->
    <?php if ($mensaje !== ''): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- ENCABEZADO -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-black">Menú</h2>

        <button onclick="abrirModalCrear()"
            class="bg-orange-600 hover:bg-orange-700 text-white px-5 py-2 rounded-xl font-bold transition">
            <i class="fa-solid fa-plus"></i> Agregar Producto
        </button>
    </div>

    <!-- TABLA -->
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-orange-100">
                <tr>
                    <th class="p-4 rounded-tl-xl">Imagen</th>
                    <th class="p-4">Producto</th>
                    <th class="p-4">Precio</th>
                    <th class="p-4">Descripción</th>
                    <th class="p-4 text-center">Stock</th>
                    <th class="p-4 rounded-tr-xl text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="5" class="p-6 text-center text-stone-400">
                        No hay productos registrados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($productos as $p):
                    $inv = $invModel->obtenerPorProducto((int)$p['id_producto']);
                    $stockActual  = $inv ? (int)$inv['cantidad_actual']  : null;
                    $stockMinimo  = $inv ? (int)$inv['cantidad_minima']  : null;
                    $estadoStock  = 'sin-registro';
                    $badgeStock   = '<span class="text-xs font-bold px-2 py-0.5 rounded-full bg-stone-100 text-stone-500">Sin registro</span>';
                    if ($inv !== false) {
                        if ($stockActual === 0) {
                            $estadoStock = 'agotado';
                            $badgeStock  = '<span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700"><i class="fa-solid fa-circle-xmark mr-1"></i>Agotado (0)</span>';
                        } elseif ($stockActual <= $stockMinimo) {
                            $estadoStock = 'bajo';
                            $badgeStock  = "<span class='text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700'><i class='fa-solid fa-triangle-exclamation mr-1'></i>Bajo ({$stockActual})</span>";
                        } else {
                            $estadoStock = 'ok';
                            $badgeStock  = "<span class='text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700'><i class='fa-solid fa-circle-check mr-1'></i>{$stockActual} uds.</span>";
                        }
                    }
                ?>
                    <tr class="border-t hover:bg-orange-50 transition">
                        <td class="p-3">
                            <?php
                                $imgSrc = '';
                                if (!empty($p['imagen'])) {
                                    $ruta = __DIR__ . '/../../img/productos/' . $p['imagen'];
                                    if (file_exists($ruta)) {
                                        $imgSrc = '../../img/productos/' . htmlspecialchars($p['imagen']);
                                    }
                                }
                            ?>
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>"
                                     alt="<?= htmlspecialchars($p['nombre']) ?>"
                                     class="w-14 h-14 rounded-xl object-cover border border-orange-100 shadow-sm">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-300 text-xl">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 font-bold">
                            <?= htmlspecialchars($p['nombre'] ?? '') ?>
                        </td>
                        <td class="p-4">
                            $<?= number_format((float)($p['precio'] ?? 0), 2) ?>
                        </td>
                        <td class="p-4 text-stone-500 text-sm max-w-xs truncate">
                            <?= htmlspecialchars($p['descripcion'] ?? '') ?>
                        </td>
                        <td class="p-4 text-center">
                            <?= $badgeStock ?>
                        </td>
                        <td class="p-4 text-center">
                            <button
                                onclick="abrirModalEditar(
                                    <?= (int)$p['id_producto'] ?>,
                                    <?= htmlspecialchars(json_encode($p['nombre'] ?? ''), ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($p['precio'] ?? ''), ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($p['descripcion'] ?? ''), ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($p['imagen'] ?? ''), ENT_QUOTES) ?>
                                )"
                                class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold px-3 py-1.5 rounded-lg transition text-sm mr-1">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>
                            <button
                                onclick="abrirModalEliminar(
                                    <?= (int)$p['id_producto'] ?>,
                                    <?= htmlspecialchars(json_encode($p['nombre'] ?? ''), ENT_QUOTES) ?>
                                )"
                                class="inline-flex items-center gap-1 bg-red-100 hover:bg-red-200 text-red-700 font-bold px-3 py-1.5 rounded-lg transition text-sm">
                                <i class="fa-solid fa-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL: CREAR PRODUCTO
     ══════════════════════════════════════════ -->
<div id="modalCrear" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md relative shadow-2xl">

        <button onclick="cerrarModalCrear()" class="absolute top-3 right-4 text-stone-400 hover:text-stone-700 text-xl">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                <i class="fa-solid fa-plus"></i>
            </div>
            <h2 class="text-xl font-black">Agregar Producto</h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="crear_producto" value="1">

            <label class="block text-sm font-bold text-stone-600 mb-1">Nombre *</label>
            <input type="text" name="nombre" placeholder="Ej. Hamburguesa clásica" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Precio *</label>
            <input type="number" step="0.01" min="0" name="precio" placeholder="Ej. 12.50" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Descripción</label>
            <textarea name="descripcion" placeholder="Descripción del producto..." rows="2"
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 resize-none"></textarea>

            <!-- IMAGEN -->
            <label class="block text-sm font-bold text-stone-600 mb-1">
                <i class="fa-solid fa-image text-orange-500 mr-1"></i> Imagen del producto
            </label>
            <div class="mb-2 border-2 border-dashed border-stone-200 rounded-xl p-3 text-center cursor-pointer hover:border-orange-400 transition"
                 onclick="document.getElementById('file_crear').click()">
                <div id="preview_crear_wrap">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-stone-300 mb-1 block"></i>
                    <p class="text-xs text-stone-400">Haz clic para seleccionar imagen</p>
                    <p class="text-xs text-stone-300">JPG, PNG, WEBP · Máx. 2 MB</p>
                </div>
                <img id="preview_crear_img" src="" alt="" class="hidden w-full h-32 object-cover rounded-lg mt-2">
            </div>
            <input type="file" name="imagen" id="file_crear" accept="image/jpeg,image/png,image/webp,image/gif"
                class="hidden" onchange="previsualizarFile(this,'preview_crear_img','preview_crear_wrap')">

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <label class="block text-sm font-bold text-stone-600 mb-1">
                        <i class="fa-solid fa-boxes-stacked text-orange-500 mr-1"></i> Stock inicial
                    </label>
                    <input type="number" name="stock_inicial" min="0" value="0" placeholder="0"
                        class="w-full p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-stone-600 mb-1">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1"></i> Stock mínimo
                    </label>
                    <input type="number" name="stock_minimo" min="0" value="5" placeholder="5"
                        class="w-full p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400">
                </div>
            </div>
            <p class="text-xs text-stone-400 mb-4 -mt-2">
                <i class="fa-solid fa-circle-info text-orange-400 mr-1"></i>
                El stock mínimo genera alertas cuando el inventario baje de ese nivel.
            </p>

            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalCrear()"
                    class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black transition">
                    <i class="fa-solid fa-plus mr-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL: EDITAR PRODUCTO
     ══════════════════════════════════════════ -->
<div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md relative shadow-2xl">

        <button onclick="cerrarModalEditar()" class="absolute top-3 right-4 text-stone-400 hover:text-stone-700 text-xl">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <h2 class="text-xl font-black">Editar Producto</h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="editar_producto" value="1">
            <input type="hidden" name="id_producto" id="edit_id">

            <label class="block text-sm font-bold text-stone-600 mb-1">Nombre *</label>
            <input type="text" name="nombre" id="edit_nombre" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Precio *</label>
            <input type="number" step="0.01" min="0" name="precio" id="edit_precio" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Descripción</label>
            <textarea name="descripcion" id="edit_descripcion" rows="2"
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 resize-none"></textarea>

            <!-- IMAGEN -->
            <label class="block text-sm font-bold text-stone-600 mb-1">
                <i class="fa-solid fa-image text-blue-500 mr-1"></i> Imagen del producto
            </label>
            <div class="mb-1 border-2 border-dashed border-stone-200 rounded-xl p-3 text-center cursor-pointer hover:border-blue-400 transition"
                 onclick="document.getElementById('file_editar').click()">
                <div id="preview_editar_wrap">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-stone-300 mb-1 block"></i>
                    <p class="text-xs text-stone-400">Haz clic para cambiar la imagen</p>
                    <p class="text-xs text-stone-300">JPG, PNG, WEBP · Máx. 2 MB</p>
                </div>
                <img id="preview_editar_img" src="" alt="" class="hidden w-full h-32 object-cover rounded-lg mt-2">
            </div>
            <p id="edit_imagen_actual_txt" class="text-xs text-stone-400 mb-4"></p>
            <input type="file" name="imagen" id="file_editar" accept="image/jpeg,image/png,image/webp,image/gif"
                class="hidden" onchange="previsualizarFile(this,'preview_editar_img','preview_editar_wrap')">

            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalEditar()"
                    class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black transition">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL: CONFIRMAR ELIMINAR
     ══════════════════════════════════════════ -->
<div id="modalEliminar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm relative shadow-2xl text-center">

        <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h2 class="text-xl font-black text-stone-900 mb-2">¿Eliminar producto?</h2>
        <p class="text-stone-500 mb-1">Estás a punto de eliminar:</p>
        <p id="eliminar_nombre" class="font-black text-orange-600 text-lg mb-5"></p>
        <p class="text-sm text-stone-400 mb-6">Esta acción no se puede deshacer.</p>

        <form method="POST">
            <input type="hidden" name="eliminar_producto" value="1">
            <input type="hidden" name="id_producto" id="eliminar_id">

            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalEliminar()"
                    class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black transition">
                    <i class="fa-solid fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </form>
    </div>
</div>


<script>
/* ── Modal Crear ── */
function abrirModalCrear() {
    document.getElementById('modalCrear').classList.remove('hidden');
    document.getElementById('modalCrear').classList.add('flex');
}
function cerrarModalCrear() {
    document.getElementById('modalCrear').classList.add('hidden');
    document.getElementById('modalCrear').classList.remove('flex');
}

/* ── Modal Editar ── */
function abrirModalEditar(id, nombre, precio, descripcion, imagen) {
    document.getElementById('edit_id').value          = id;
    document.getElementById('edit_nombre').value      = nombre;
    document.getElementById('edit_precio').value      = precio;
    document.getElementById('edit_descripcion').value = descripcion;

    // Mostrar imagen actual si existe
    var prevImg  = document.getElementById('preview_editar_img');
    var prevWrap = document.getElementById('preview_editar_wrap');
    var txtActual = document.getElementById('edit_imagen_actual_txt');

    if (imagen) {
        prevImg.src = '../../img/productos/' + imagen;
        prevImg.classList.remove('hidden');
        prevWrap.style.display = 'none';
        txtActual.innerHTML = '<i class="fa-solid fa-circle-check text-green-500 mr-1"></i>Imagen actual: <strong>' + imagen + '</strong>. Selecciona un archivo para reemplazarla.';
    } else {
        prevImg.src = '';
        prevImg.classList.add('hidden');
        prevWrap.style.display = 'block';
        txtActual.textContent = '';
    }

    // Limpiar el input file
    document.getElementById('file_editar').value = '';

    document.getElementById('modalEditar').classList.remove('hidden');
    document.getElementById('modalEditar').classList.add('flex');
}
function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.getElementById('modalEditar').classList.remove('flex');
}

/* ── Previsualizar archivo seleccionado ── */
function previsualizarFile(input, imgId, wrapId) {
    var file = input.files[0];
    if (!file) return;

    var reader = new FileReader();
    reader.onload = function(e) {
        var img  = document.getElementById(imgId);
        var wrap = document.getElementById(wrapId);
        img.src  = e.target.result;
        img.classList.remove('hidden');
        if (wrap) wrap.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

/* ── Modal Eliminar ── */
function abrirModalEliminar(id, nombre) {
    document.getElementById('eliminar_id').value       = id;
    document.getElementById('eliminar_nombre').textContent = nombre;

    document.getElementById('modalEliminar').classList.remove('hidden');
    document.getElementById('modalEliminar').classList.add('flex');
}
function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.add('hidden');
    document.getElementById('modalEliminar').classList.remove('flex');
}

/* ── Cerrar modales al hacer clic fuera ── */
['modalCrear', 'modalEditar', 'modalEliminar'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            this.classList.remove('flex');
        }
    });
});

/* ── Cerrar con Escape ── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCrear();
        cerrarModalEditar();
        cerrarModalEliminar();
    }
});

<?php if ($error !== ''): ?>
    // Reabrir modal de crear si hubo error al crear
    <?php if (isset($_POST['crear_producto'])): ?>
        document.addEventListener('DOMContentLoaded', abrirModalCrear);
    <?php endif; ?>
<?php endif; ?>
</script>

<?php require_once __DIR__.'/../layouts/footer.php'; ?>
