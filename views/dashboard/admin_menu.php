<?php
session_start();
if (!isset($_SESSION['usuario'])) { 
    header('Location: ../usuarios/login.php'); 
    exit; 
}

require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../models/Producto.php';

$db = (new Database())->conectar();
$productoModel = new Producto($db);

$mensaje = '';
$error   = '';

/* =========================
   CREAR PRODUCTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $nombre      = trim($_POST['nombre']      ?? '');
    $precio      = trim($_POST['precio']      ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '' || $precio === '') {
        $error = "El nombre y el precio son obligatorios.";
    } elseif ($productoModel->crear($nombre, $precio, $descripcion)) {
        $mensaje = "Producto agregado correctamente.";
    } else {
        $error = "Error al agregar el producto.";
    }
}

/* =========================
   ACTUALIZAR PRODUCTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    $id          = (int)($_POST['id_producto']      ?? 0);
    $nombre      = trim($_POST['nombre']            ?? '');
    $precio      = trim($_POST['precio']            ?? '');
    $descripcion = trim($_POST['descripcion']       ?? '');

    if ($id <= 0 || $nombre === '' || $precio === '') {
        $error = "Datos inválidos para actualizar.";
    } elseif ($productoModel->actualizar($id, $nombre, $precio, $descripcion)) {
        $mensaje = "Producto actualizado correctamente.";
    } else {
        $error = "Error al actualizar el producto.";
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
                    <th class="p-4 rounded-tl-xl">Producto</th>
                    <th class="p-4">Precio</th>
                    <th class="p-4">Descripción</th>
                    <th class="p-4 rounded-tr-xl text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="4" class="p-6 text-center text-stone-400">
                        No hay productos registrados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($productos as $p): ?>
                    <tr class="border-t hover:bg-orange-50 transition">
                        <td class="p-4 font-bold">
                            <?= htmlspecialchars($p['nombre'] ?? '') ?>
                        </td>
                        <td class="p-4">
                            $<?= number_format((float)($p['precio'] ?? 0), 2) ?>
                        </td>
                        <td class="p-4 text-stone-500">
                            <?= htmlspecialchars($p['descripcion'] ?? '') ?>
                        </td>
                        <td class="p-4 text-center">
                            <!-- Botón Editar -->
                            <button
                                onclick="abrirModalEditar(
                                    <?= (int)$p['id_producto'] ?>,
                                    <?= htmlspecialchars(json_encode($p['nombre'] ?? ''), ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($p['precio'] ?? ''), ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($p['descripcion'] ?? ''), ENT_QUOTES) ?>
                                )"
                                class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold px-3 py-1.5 rounded-lg transition text-sm mr-1">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>

                            <!-- Botón Eliminar -->
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

        <form method="POST">
            <input type="hidden" name="crear_producto" value="1">

            <label class="block text-sm font-bold text-stone-600 mb-1">Nombre *</label>
            <input type="text" name="nombre" placeholder="Ej. Hamburguesa clásica" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Precio *</label>
            <input type="number" step="0.01" min="0" name="precio" placeholder="Ej. 12.50" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Descripción</label>
            <textarea name="descripcion" placeholder="Descripción del producto..." rows="3"
                class="w-full mb-5 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 resize-none"></textarea>

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

        <form method="POST">
            <input type="hidden" name="editar_producto" value="1">
            <input type="hidden" name="id_producto" id="edit_id">

            <label class="block text-sm font-bold text-stone-600 mb-1">Nombre *</label>
            <input type="text" name="nombre" id="edit_nombre" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Precio *</label>
            <input type="number" step="0.01" min="0" name="precio" id="edit_precio" required
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400">

            <label class="block text-sm font-bold text-stone-600 mb-1">Descripción</label>
            <textarea name="descripcion" id="edit_descripcion" rows="3"
                class="w-full mb-5 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 resize-none"></textarea>

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
function abrirModalEditar(id, nombre, precio, descripcion) {
    document.getElementById('edit_id').value          = id;
    document.getElementById('edit_nombre').value      = nombre;
    document.getElementById('edit_precio').value      = precio;
    document.getElementById('edit_descripcion').value = descripcion;

    document.getElementById('modalEditar').classList.remove('hidden');
    document.getElementById('modalEditar').classList.add('flex');
}
function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.getElementById('modalEditar').classList.remove('flex');
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
