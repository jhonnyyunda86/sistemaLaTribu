<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuarios/login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Mesa.php';

$db        = (new Database())->conectar();
$mesaModel = new Mesa($db);

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_mesa'])) {
    $numero    = $_POST['numero_mesa'] ?? '';
    $capacidad = $_POST['capacidad']   ?? '';
    $estado    = $_POST['estado']      ?? 'disponible';

    if ($mesaModel->crear($numero, $capacidad, $estado)) {
        $mensaje = "Mesa creada correctamente.";
    } else {
        $error = "Error al crear la mesa.";
    }
}

$mesas  = $mesaModel->obtenerTodos();
$titulo = 'Mesas';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Mensajes -->
    <?php if ($mensaje !== ''): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-2xl flex items-center gap-2 font-semibold">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-2xl flex items-center gap-2 font-semibold">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Encabezado -->
    <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-xl shadow">
                    <i class="fa-solid fa-chair"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-stone-900">Gestión de Mesas</h2>
                    <p class="text-sm text-stone-400"><?= count($mesas) ?> mesa(s) registrada(s)</p>
                </div>
            </div>
            <button onclick="abrirModalMesa()"
                class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-5 py-2.5 rounded-xl font-bold transition">
                <i class="fa-solid fa-plus"></i> Agregar Mesa
            </button>
        </div>
    </div>

    <!-- Grid de mesas -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($mesas as $m):
            $estado    = $m['estado'] ?? 'disponible';
            $iconColor = match($estado) {
                'disponible'  => 'text-green-500',
                'reservada'   => 'text-amber-500',
                'ocupada'     => 'text-red-500',
                default       => 'text-stone-400',
            };
            $badgeColor = match($estado) {
                'disponible'  => 'bg-green-100 text-green-700',
                'reservada'   => 'bg-amber-100 text-amber-700',
                'ocupada'     => 'bg-red-100 text-red-700',
                default       => 'bg-stone-100 text-stone-500',
            };
        ?>
        <div class="bg-white rounded-2xl shadow border-2 border-stone-100 p-5 text-center hover:-translate-y-1 transition">
            <i class="fa-solid fa-chair text-5xl <?= $iconColor ?> mb-3 block"></i>
            <p class="text-xl font-black text-stone-800">Mesa #<?= (int)$m['numero_mesa'] ?></p>
            <p class="text-sm text-stone-400 mt-1 mb-3">
                <i class="fa-solid fa-users mr-1"></i><?= (int)$m['capacidad'] ?> personas
            </p>
            <span class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full <?= $badgeColor ?> mb-3">
                <?= ucfirst($estado) ?>
            </span>
            <select onchange="cambiarEstado(<?= (int)$m['id_mesa'] ?>, this.value)"
                class="w-full mt-2 p-2 border-2 border-stone-200 rounded-xl text-sm font-bold focus:outline-none focus:border-orange-400">
                <option value="disponible" <?= $estado==='disponible'?'selected':'' ?>>Disponible</option>
                <option value="ocupada"    <?= $estado==='ocupada'   ?'selected':'' ?>>Ocupada</option>
                <option value="reservada"  <?= $estado==='reservada' ?'selected':'' ?>>Reservada</option>
                <option value="mantenimiento" <?= $estado==='mantenimiento'?'selected':'' ?>>Mantenimiento</option>
            </select>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Modal agregar mesa -->
<div id="modalMesa" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md relative shadow-2xl">
        <button onclick="cerrarModalMesa()" class="absolute top-3 right-4 text-stone-400 hover:text-stone-700 text-xl">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                <i class="fa-solid fa-chair"></i>
            </div>
            <h2 class="text-xl font-black">Agregar Mesa</h2>
        </div>
        <form method="POST">
            <input type="hidden" name="crear_mesa" value="1">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Número de mesa *</label>
            <input type="number" name="numero_mesa" required placeholder="Ej. 5"
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Capacidad *</label>
            <input type="number" name="capacidad" required placeholder="Ej. 4"
                class="w-full mb-4 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
            <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Estado inicial</label>
            <select name="estado" class="w-full mb-5 p-2.5 border border-stone-200 rounded-xl focus:outline-none focus:border-orange-400 text-sm">
                <option value="disponible">Disponible</option>
                <option value="ocupada">Ocupada</option>
                <option value="reservada">Reservada</option>
                <option value="mantenimiento">Mantenimiento</option>
            </select>
            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalMesa()"
                    class="flex-1 py-2.5 rounded-xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition text-sm">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black transition text-sm">
                    <i class="fa-solid fa-plus mr-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalMesa() {
    document.getElementById('modalMesa').classList.remove('hidden');
    document.getElementById('modalMesa').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function cerrarModalMesa() {
    document.getElementById('modalMesa').classList.add('hidden');
    document.getElementById('modalMesa').classList.remove('flex');
    document.body.style.overflow = '';
}
document.getElementById('modalMesa').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalMesa();
});

function cambiarEstado(id, estado) {
    fetch('./actualizar_estado_mesa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id) + '&estado=' + encodeURIComponent(estado)
    })
    .then(r => r.text())
    .then(data => {
        if (data !== 'ok') alert('Error al actualizar el estado');
    })
    .catch(() => alert('Error de conexión'));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
