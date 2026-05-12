<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioAdmin.php';

$db    = (new Database())->conectar();
$model = new UsuarioAdmin($db);

$mensaje = '';
$error   = '';

/* =========================
   AGREGAR USUARIO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $correo   = trim($_POST['correo']   ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = trim($_POST['rol']      ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$nombre || !$correo || !$telefono || !$rol || !$password) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es valido.';
    } elseif ($password !== $confirm) {
        $error = 'Las contrasenas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contrasena debe tener al menos 6 caracteres.';
    } elseif ($model->existeCorreo($correo)) {
        $error = 'Este correo ya esta registrado.';
    } else {
        $ok = $model->crear([
            'nombre'   => $nombre,
            'correo'   => $correo,
            'telefono' => $telefono,
            'role'     => $rol,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        if ($ok) { $mensaje = "Usuario creado correctamente."; $_POST = []; }
        else      { $error   = 'Error al crear el usuario.'; }
    }
}

/* =========================
   EDITAR USUARIO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar') {
    $id       = (int)($_POST['id_usuario'] ?? 0);
    $nombre   = trim($_POST['nombre']      ?? '');
    $correo   = trim($_POST['correo']      ?? '');
    $telefono = trim($_POST['telefono']    ?? '');
    $rol      = trim($_POST['rol']         ?? '');

    if (!$id || !$nombre || !$correo || !$rol) {
        $error = 'Datos invalidos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es valido.';
    } elseif ($model->existeCorreo($correo, $id)) {
        $error = 'Ese correo ya lo usa otro usuario.';
    } else {
        $ok = $model->actualizar($id, [
            'nombre'   => $nombre,
            'correo'   => $correo,
            'telefono' => $telefono,
            'role'     => $rol,
        ]);
        if ($ok && !empty($_POST['password'])) {
            $np = $_POST['password'];
            $cp = $_POST['confirm'] ?? '';
            if ($np !== $cp)        { $error = 'Las contrasenas no coinciden.'; }
            elseif (strlen($np) < 6){ $error = 'Minimo 6 caracteres.'; }
            else                    { $model->actualizarPassword($id, $np); }
        }
        if (!$error) $mensaje = $ok ? 'Usuario actualizado.' : 'Error al actualizar.';
    }
}

/* =========================
   TOGGLE ACTIVO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'toggle') {
    $id = (int)($_POST['id_usuario'] ?? 0);
    if ($id > 0 && $model->toggleActivo($id)) $mensaje = 'Estado actualizado.';
    else $error = 'Error al cambiar estado.';
}

/* =========================
   ELIMINAR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $id = (int)($_POST['id_usuario'] ?? 0);
    $sesionId = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    if ($id === $sesionId) {
        $error = 'No puedes eliminar tu propia cuenta.';
    } elseif ($id > 0 && $model->eliminar($id)) {
        $mensaje = 'Usuario eliminado.';
    } else {
        $error = 'Error al eliminar.';
    }
}

// Todos los roles
$usuarios = $model->obtenerTodos();

$titulo = 'Gestion de Usuarios';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="bg-white rounded-2xl shadow p-6">

    <?php if ($mensaje !== ''): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded-xl mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded-xl mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-stone-900">Todos los Usuarios</h2>
            <p class="text-sm text-stone-400"><?= count($usuarios) ?> usuario(s) registrado(s)</p>
        </div>
        <button onclick="abrir('modalCrear')"
            class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-5 py-2.5 rounded-xl font-bold transition">
            <i class="fa-solid fa-user-plus"></i> Agregar Usuario
        </button>
    </div>

    <div class="flex flex-wrap gap-3 mb-5">
        <button onclick="filtrarRol('todos')"   id="btn-todos"   class="filtro-btn activo-filtro px-4 py-1.5 rounded-full text-sm font-bold border transition">Todos</button>
        <button onclick="filtrarRol('admin')"   id="btn-admin"   class="filtro-btn px-4 py-1.5 rounded-full text-sm font-bold border transition">Administradores</button>
        <button onclick="filtrarRol('mesero')"  id="btn-mesero"  class="filtro-btn px-4 py-1.5 rounded-full text-sm font-bold border transition">Meseros</button>
        <button onclick="filtrarRol('cliente')" id="btn-cliente" class="filtro-btn px-4 py-1.5 rounded-full text-sm font-bold border transition">Clientes</button>
        <input type="text" id="buscador" placeholder="Buscar..." oninput="filtrarTabla()"
            class="ml-auto w-56 p-2 border border-stone-200 rounded-xl text-sm focus:outline-none focus:border-orange-400">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-orange-50 text-stone-600 uppercase text-xs font-bold">
                <tr>
                    <th class="p-4 rounded-tl-xl">#</th>
                    <th class="p-4">Nombre</th>
                    <th class="p-4">Correo</th>
                    <th class="p-4">Telefono</th>
                    <th class="p-4 text-center">Rol</th>
                    <th class="p-4 text-center">Estado</th>
                    <th class="p-4 rounded-tr-xl text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpoTabla">
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="7" class="p-8 text-center text-stone-400">No hay usuarios registrados.</td></tr>
            <?php else: ?>
                <?php $i = 1; foreach ($usuarios as $u): ?>
                <tr class="border-t hover:bg-orange-50 transition fila-usuario" data-rol="<?= htmlspecialchars($u['role']) ?>">
                    <td class="p-4 text-stone-400 font-mono"><?= $i++ ?></td>
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm
                                <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-600' : ($u['role'] === 'mesero' ? 'bg-blue-100 text-blue-600' : 'bg-orange-100 text-orange-600') ?>">
                                <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                            </div>
                            <span class="font-bold text-stone-800"><?= htmlspecialchars($u['nombre']) ?></span>
                        </div>
                    </td>
                    <td class="p-4 text-stone-500"><?= htmlspecialchars($u['correo']) ?></td>
                    <td class="p-4 text-stone-500"><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                    <td class="p-4 text-center">
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-700 text-xs font-bold px-3 py-1 rounded-full">
                                <i class="fa-solid fa-shield-halved"></i> Admin
                            </span>
                        <?php elseif ($u['role'] === 'mesero'): ?>
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">
                                <i class="fa-solid fa-user-tie"></i> Mesero
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full">
                                <i class="fa-solid fa-user"></i> Cliente
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-center">
                        <?php if ((int)($u['activo'] ?? 1) === 1): ?>
                            <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">
                                <i class="fa-solid fa-circle text-[8px]"></i> Activo
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-stone-100 text-stone-500 text-xs font-bold px-3 py-1 rounded-full">
                                <i class="fa-solid fa-circle text-[8px]"></i> Inactivo
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-1 flex-wrap">

                            <button onclick="abrirEditar(
                                    <?= (int)$u['id_usuario'] ?>,
                                    <?= htmlspecialchars(json_encode($u['nombre']),    ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($u['correo']),    ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($u['telefono'] ?? ''), ENT_QUOTES) ?>,
                                    <?= htmlspecialchars(json_encode($u['role']),      ENT_QUOTES) ?>
                                )"
                                class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold px-3 py-1.5 rounded-lg transition text-xs">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>

                            <form method="POST" class="inline">
                                <input type="hidden" name="accion"     value="toggle">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <?php if ((int)($u['activo'] ?? 1) === 1): ?>
                                    <button type="submit" class="inline-flex items-center gap-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-bold px-3 py-1.5 rounded-lg transition text-xs">
                                        <i class="fa-solid fa-ban"></i> Desactivar
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="inline-flex items-center gap-1 bg-green-100 hover:bg-green-200 text-green-700 font-bold px-3 py-1.5 rounded-lg transition text-xs">
                                        <i class="fa-solid fa-circle-check"></i> Activar
                                    </button>
                                <?php endif; ?>
                            </form>

                            <?php $sesionId = (int)($_SESSION['usuario']['id_usuario'] ?? 0); ?>
                            <?php if ((int)$u['id_usuario'] !== $sesionId): ?>
                            <button onclick="abrirEliminar(<?= (int)$u['id_usuario'] ?>, <?= htmlspecialchars(json_encode($u['nombre']), ENT_QUOTES) ?>)"
                                class="inline-flex items-center gap-1 bg-red-100 hover:bg-red-200 text-red-700 font-bold px-3 py-1.5 rounded-lg transition text-xs">
                                <i class="fa-solid fa-trash"></i> Eliminar
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-stone-100 text-stone-400 px-3 py-1.5 rounded-lg text-xs font-bold cursor-not-allowed" title="No puedes eliminar tu propia cuenta">
                                <i class="fa-solid fa-lock"></i> Tu cuenta
                            </span>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CREAR -->
<div id="modalCrear" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md relative shadow-2xl">
        <button onclick="cerrar('modalCrear')" class="absolute top-3 right-4 text-stone-400 hover:text-stone-700 text-xl"><i class="fa-solid fa-xmark"></i></button>
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-user-plus"></i></div>
            <div><h2 class="text-xl font-black">Agregar Usuario</h2><p class="text-xs text-stone-400">Mesero o Administrador</p></div>
        </div>
        <?php if ($error !== '' && ($_POST['accion'] ?? '') === 'crear'): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-semibold">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="accion" value="crear">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Nombre *</label>
            <input type="text" name="nombre" required placeholder="Nombre completo"
                value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                class="w-full mb-3 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Correo *</label>
            <input type="email" name="correo" required placeholder="correo@ejemplo.com"
                value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                class="w-full mb-3 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Telefono *</label>
            <input type="tel" name="telefono" required placeholder="Ej. 3001234567"
                value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                class="w-full mb-3 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Rol *</label>
            <select name="rol" required class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
                <option value="" disabled selected>Selecciona un rol</option>
                <option value="admin"   <?= (($_POST['rol'] ?? '') === 'admin')   ? 'selected' : '' ?>>Administrador</option>
                <option value="mesero"  <?= (($_POST['rol'] ?? '') === 'mesero')  ? 'selected' : '' ?>>Mesero</option>
                <option value="cliente" <?= (($_POST['rol'] ?? '') === 'cliente') ? 'selected' : '' ?>>Cliente</option>
            </select>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Contrasena *</label>
                    <input type="password" name="password" required placeholder="Min. 6 caracteres"
                        class="w-full p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Confirmar *</label>
                    <input type="password" name="confirm" required placeholder="Repite"
                        class="w-full p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="cerrar('modalCrear')" class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition text-sm">Cancelar</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black transition text-sm"><i class="fa-solid fa-user-plus mr-1"></i> Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md relative shadow-2xl">
        <button onclick="cerrar('modalEditar')" class="absolute top-3 right-4 text-stone-400 hover:text-stone-700 text-xl"><i class="fa-solid fa-xmark"></i></button>
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fa-solid fa-pen-to-square"></i></div>
            <div><h2 class="text-xl font-black">Editar Usuario</h2><p class="text-xs text-stone-400">Puedes cambiar el rol y la contrasena</p></div>
        </div>
        <form method="POST">
            <input type="hidden" name="accion"     value="editar">
            <input type="hidden" name="id_usuario" id="edit_id">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Nombre *</label>
            <input type="text" name="nombre" id="edit_nombre" required
                class="w-full mb-3 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Correo *</label>
            <input type="email" name="correo" id="edit_correo" required
                class="w-full mb-3 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Telefono</label>
            <input type="tel" name="telefono" id="edit_telefono"
                class="w-full mb-3 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Rol *</label>
            <select name="rol" id="edit_rol" required class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 text-sm">
                <option value="admin">Administrador</option>
                <option value="mesero">Mesero</option>
                <option value="cliente">Cliente</option>
            </select>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Nueva contrasena</label>
                    <input type="password" name="password" placeholder="Dejar en blanco"
                        class="w-full p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Confirmar</label>
                    <input type="password" name="confirm" placeholder="Dejar en blanco"
                        class="w-full p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-blue-400 text-sm">
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="cerrar('modalEditar')" class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition text-sm">Cancelar</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black transition text-sm"><i class="fa-solid fa-floppy-disk mr-1"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm relative shadow-2xl text-center">
        <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2 class="text-xl font-black text-stone-900 mb-2">Eliminar usuario</h2>
        <p class="text-stone-500 mb-1 text-sm">Estas a punto de eliminar a:</p>
        <p id="eliminar_nombre" class="font-black text-orange-600 text-lg mb-2"></p>
        <p class="text-xs text-stone-400 mb-6">Esta accion no se puede deshacer.</p>
        <form method="POST">
            <input type="hidden" name="accion"     value="eliminar">
            <input type="hidden" name="id_usuario" id="eliminar_id">
            <div class="flex gap-3">
                <button type="button" onclick="cerrar('modalEliminar')" class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition text-sm">Cancelar</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black transition text-sm"><i class="fa-solid fa-trash mr-1"></i> Eliminar</button>
            </div>
        </form>
    </div>
</div>

<style>
.filtro-btn { background: white; color: #78716c; border-color: #e7e5e4; }
.filtro-btn:hover { background: #fff7ed; color: #ea580c; border-color: #fdba74; }
.activo-filtro { background: #ea580c !important; color: white !important; border-color: #ea580c !important; }
</style>

<script>
function abrir(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function cerrar(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirEditar(id, nombre, correo, telefono, rol) {
    document.getElementById('edit_id').value       = id;
    document.getElementById('edit_nombre').value   = nombre;
    document.getElementById('edit_correo').value   = correo;
    document.getElementById('edit_telefono').value = telefono;
    document.getElementById('edit_rol').value      = rol;
    abrir('modalEditar');
}
function abrirEliminar(id, nombre) {
    document.getElementById('eliminar_id').value          = id;
    document.getElementById('eliminar_nombre').textContent = nombre;
    abrir('modalEliminar');
}

['modalCrear','modalEditar','modalEliminar'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) cerrar(id);
    });
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') ['modalCrear','modalEditar','modalEliminar'].forEach(cerrar);
});

// Filtro por rol
var rolActivo = 'todos';
function filtrarRol(rol) {
    rolActivo = rol;
    document.querySelectorAll('.filtro-btn').forEach(function(b) { b.classList.remove('activo-filtro'); });
    document.getElementById('btn-' + rol).classList.add('activo-filtro');
    filtrarTabla();
}
function filtrarTabla() {
    var q = document.getElementById('buscador').value.toLowerCase();
    document.querySelectorAll('.fila-usuario').forEach(function(fila) {
        var rolOk  = rolActivo === 'todos' || fila.dataset.rol === rolActivo;
        var textoOk = fila.textContent.toLowerCase().includes(q);
        fila.style.display = (rolOk && textoOk) ? '' : 'none';
    });
}

<?php if ($error !== '' && ($_POST['accion'] ?? '') === 'crear'): ?>
    document.addEventListener('DOMContentLoaded', function() { abrir('modalCrear'); });
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
