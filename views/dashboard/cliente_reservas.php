<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuarios/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reserva.php';
require_once __DIR__ . '/../../models/Mesa.php';

$db           = (new Database())->conectar();
$reservaModel = new Reserva($db);
$mesaModel    = new Mesa($db);

$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
$mensaje   = '';
$error     = '';

$idCliente = $reservaModel->obtenerIdCliente($idUsuario);
if (!$idCliente) {
    $telefono  = $_SESSION['usuario']['telefono'] ?? '';
    $idCliente = $reservaModel->crearCliente($idUsuario, $telefono);
}

/* ── CREAR RESERVA ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reservar') {
    $idMesa   = (int)($_POST['id_mesa']        ?? 0);
    $fecha    = trim($_POST['fecha']            ?? '');
    $hora     = trim($_POST['hora']             ?? '');
    $personas = (int)($_POST['numero_personas'] ?? 0);

    if (!$idMesa || !$fecha || !$hora || $personas < 1) {
        $error = 'Completa todos los campos.';
    } elseif (strtotime($fecha) < strtotime(date('Y-m-d'))) {
        $error = 'La fecha no puede ser en el pasado.';
    } elseif ($reservaModel->mesaOcupadaEnFecha($idMesa, $fecha, $hora)) {
        $error = 'Esa mesa ya tiene una reserva en ese horario. Elige otra hora o mesa.';
    } else {
        $mesa = $mesaModel->obtenerPorId($idMesa);
        if (!$mesa || $mesa['estado'] === 'mantenimiento') {
            $error = 'La mesa seleccionada no está disponible.';
        } elseif ($personas > (int)$mesa['capacidad']) {
            $error = "La mesa #{$mesa['numero_mesa']} tiene capacidad máxima de {$mesa['capacidad']} personas.";
        } else {
            $ok = $reservaModel->crear($idCliente, $idMesa, $fecha, $hora, $personas);
            if ($ok) {
                $mesaModel->actualizarEstado($idMesa, 'reservada');
                $mensaje = "¡Reserva confirmada! Mesa #{$mesa['numero_mesa']} — {$fecha} a las {$hora}.";
            } else {
                $error = 'Error al guardar la reserva. Intenta de nuevo.';
            }
        }
    }
}

/* ── CANCELAR RESERVA ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar') {
    $idReserva    = (int)($_POST['id_reserva'] ?? 0);
    $idMesaLiberar = (int)($_POST['id_mesa']   ?? 0);
    if ($idReserva > 0 && $reservaModel->cancelar($idReserva, $idCliente)) {
        if ($idMesaLiberar > 0) $mesaModel->actualizarEstado($idMesaLiberar, 'disponible');
        $mensaje = 'Reserva cancelada. La mesa quedó disponible.';
    } else {
        $error = 'No se pudo cancelar la reserva.';
    }
}

$mesas    = $mesaModel->obtenerTodos();
$reservas = $reservaModel->obtenerPorCliente($idCliente);
$titulo   = 'Mis Reservas';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    /* ── Tarjeta de mesa ── */
    .mesa-card {
        transition: all .2s ease;
        cursor: pointer;
    }
    .mesa-card:hover:not(.bloqueada) {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(234,88,12,.2);
    }
    .mesa-card.seleccionada {
        border-color: #ea580c !important;
        background: linear-gradient(135deg, #fff7ed, #ffedd5) !important;
        box-shadow: 0 0 0 3px rgba(234,88,12,.25), 0 12px 30px rgba(234,88,12,.2);
        transform: translateY(-4px);
    }
    .mesa-card.bloqueada {
        cursor: not-allowed;
        opacity: .45;
    }
    .mesa-card.insuficiente {
        opacity: .35;
    }

    /* ── Sección con fondo oscuro tipo dashboard ── */
    .reserva-bg {
        background: rgba(255,247,237,.93);
        backdrop-filter: blur(14px);
        border: 1px solid rgba(251,146,60,.2);
        border-radius: 28px;
        padding: 2rem;
    }

    /* ── Input / select ── */
    .inp {
        width: 100%;
        padding: .85rem 1.1rem;
        border: 2px solid #e7e5e4;
        border-radius: 14px;
        font-size: 1rem;
        background: #fafaf9;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    .inp:focus {
        border-color: #ea580c;
        box-shadow: 0 0 0 3px rgba(234,88,12,.12);
        background: #fff;
    }

    /* ── Badge estado ── */
    .badge { display:inline-flex; align-items:center; gap:.3rem; font-size:.8rem; font-weight:700; padding:.3rem .85rem; border-radius:999px; }
</style>


<div class="space-y-8">

    <!-- ══ ALERTAS ══ -->
    <?php if ($mensaje !== ''): ?>
        <div class="flex items-center gap-4 p-5 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-xl shadow-green-500/20">
            <i class="fa-solid fa-circle-check text-3xl"></i>
            <p class="font-bold text-lg"><?= htmlspecialchars($mensaje) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="flex items-center gap-4 p-5 rounded-2xl bg-gradient-to-r from-red-500 to-rose-500 text-white shadow-xl shadow-red-500/20">
            <i class="fa-solid fa-circle-exclamation text-3xl"></i>
            <p class="font-bold text-lg"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>


    <!-- ══ FORMULARIO NUEVA RESERVA ══ -->
    <div class="reserva-bg">

        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-2xl shadow-lg">
                <i class="fa-solid fa-calendar-plus"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-stone-900">Nueva Reserva</h2>
                <p class="text-base text-stone-500 mt-0.5">Elige fecha, hora, personas y mesa</p>
            </div>
        </div>

        <form method="POST" id="formReserva">
            <input type="hidden" name="accion"   value="reservar">
            <input type="hidden" name="id_mesa"  id="inp_mesa">

            <!-- Fila de campos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

                <!-- Fecha -->
                <div>
                    <label class="block text-sm font-black text-stone-600 uppercase tracking-wide mb-2">
                        <i class="fa-solid fa-calendar text-orange-500 mr-1"></i> Fecha
                    </label>
                    <input type="date" name="fecha" id="inp_fecha" required
                        min="<?= date('Y-m-d') ?>"
                        class="inp"
                        onchange="actualizarMesas()">
                </div>

                <!-- Hora -->
                <div>
                    <label class="block text-sm font-black text-stone-600 uppercase tracking-wide mb-2">
                        <i class="fa-solid fa-clock text-orange-500 mr-1"></i> Hora
                    </label>
                    <select name="hora" id="inp_hora" required class="inp" onchange="actualizarMesas()">
                        <option value="">Selecciona una hora</option>
                        <optgroup label="Almuerzo">
                            <?php foreach (['12:00','12:30','13:00','13:30','14:00','14:30'] as $h): ?>
                                <option value="<?= $h ?>"><?= $h ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Cena">
                            <?php foreach (['18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30'] as $h): ?>
                                <option value="<?= $h ?>"><?= $h ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Personas -->
                <div>
                    <label class="block text-sm font-black text-stone-600 uppercase tracking-wide mb-2">
                        <i class="fa-solid fa-users text-orange-500 mr-1"></i> Personas
                    </label>
                    <input type="number" name="numero_personas" id="inp_personas"
                        min="1" max="20" required placeholder="Ej. 4"
                        class="inp"
                        oninput="filtrarMesasPorPersonas()">
                </div>
            </div>

            <!-- Selector de mesas -->
            <div>
                <p class="text-sm font-black text-stone-600 uppercase tracking-wide mb-4">
                    <i class="fa-solid fa-chair text-orange-500 mr-1"></i> Selecciona una mesa
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4" id="contenedor_mesas">
                    <?php foreach ($mesas as $m):
                        $estado    = $m['estado'];
                        $bloqueada = in_array($estado, ['ocupada', 'mantenimiento']);
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
                    <div class="mesa-card <?= $bloqueada ? 'bloqueada' : '' ?> border-2 border-stone-200 bg-white rounded-2xl p-5 text-center"
                        data-id="<?= (int)$m['id_mesa'] ?>"
                        data-estado="<?= htmlspecialchars($estado) ?>"
                        data-capacidad="<?= (int)$m['capacidad'] ?>"
                        data-numero="<?= (int)$m['numero_mesa'] ?>"
                        onclick="seleccionarMesa(this)">

                        <i class="fa-solid fa-chair text-5xl <?= $iconColor ?> mb-3 block"></i>

                        <p class="text-xl font-black text-stone-800">Mesa #<?= (int)$m['numero_mesa'] ?></p>

                        <p class="text-sm text-stone-500 mt-1 mb-3">
                            <i class="fa-solid fa-users mr-1"></i><?= (int)$m['capacidad'] ?> personas máx.
                        </p>

                        <span class="badge <?= $badgeColor ?>">
                            <?php if ($estado === 'disponible'): ?>
                                <i class="fa-solid fa-circle-check"></i>
                            <?php elseif ($estado === 'reservada'): ?>
                                <i class="fa-solid fa-calendar-check"></i>
                            <?php elseif ($estado === 'ocupada'): ?>
                                <i class="fa-solid fa-ban"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            <?php endif; ?>
                            <?= ucfirst($estado) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Leyenda -->
                <div class="flex flex-wrap gap-5 mt-5 text-sm font-semibold text-stone-500">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-chair text-green-500 text-lg"></i> Disponible</span>
                    <span class="flex items-center gap-2"><i class="fa-solid fa-chair text-amber-500 text-lg"></i> Reservada</span>
                    <span class="flex items-center gap-2"><i class="fa-solid fa-chair text-red-500 text-lg"></i> Ocupada</span>
                    <span class="flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation text-stone-400 text-lg"></i> Mantenimiento</span>
                </div>

                <!-- Mesa seleccionada -->
                <div id="mesa_seleccionada_txt" class="hidden mt-4 inline-flex items-center gap-3 bg-orange-100 border border-orange-300 text-orange-800 font-bold px-5 py-3 rounded-2xl text-base">
                    <i class="fa-solid fa-circle-check text-orange-600 text-xl"></i>
                    <span id="mesa_seleccionada_nombre"></span>
                </div>
            </div>

            <!-- Botón -->
            <div class="mt-8">
                <button type="submit" id="btn_reservar" disabled
                    class="inline-flex items-center gap-3 px-10 py-4 rounded-2xl text-lg font-black
                           bg-gradient-to-r from-orange-600 to-amber-500 text-white shadow-xl shadow-orange-500/30
                           hover:opacity-90 hover:-translate-y-0.5 transition
                           disabled:opacity-40 disabled:cursor-not-allowed disabled:translate-y-0 disabled:shadow-none">
                    <i class="fa-solid fa-calendar-check text-xl"></i>
                    Confirmar Reserva
                </button>
            </div>
        </form>
    </div>


    <!-- ══ MIS RESERVAS ══ -->
    <div class="reserva-bg">

        <div class="flex items-center gap-4 mb-7">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-2xl shadow-lg">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-stone-900">Mis Reservas</h2>
                <p class="text-base text-stone-500 mt-0.5"><?= count($reservas) ?> reserva(s) registrada(s)</p>
            </div>
        </div>

        <?php if (empty($reservas)): ?>
            <div class="text-center py-14 text-stone-400">
                <i class="fa-solid fa-calendar-xmark text-6xl mb-4 block text-stone-300"></i>
                <p class="text-xl font-bold text-stone-400">Aún no tienes reservas</p>
                <p class="text-base text-stone-400 mt-1">¡Haz tu primera reserva arriba!</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($reservas as $r):
                    $estado    = $r['nombre_estado'] ?? 'Pendiente';
                    $cancelada = $estado === 'Cancelada';
                    $badgeClass = match($estado) {
                        'Confirmada' => 'bg-green-100 text-green-700',
                        'Cancelada'  => 'bg-red-100 text-red-700',
                        default      => 'bg-amber-100 text-amber-700',
                    };
                    $cardClass = $cancelada
                        ? 'border-stone-200 bg-stone-50/60 opacity-60'
                        : 'border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50';
                ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 rounded-2xl border-2 <?= $cardClass ?>">

                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-white border-2 border-orange-200 flex items-center justify-center shadow-sm flex-shrink-0">
                            <i class="fa-solid fa-chair text-3xl text-orange-500"></i>
                        </div>
                        <div>
                            <p class="text-xl font-black text-stone-900">
                                Mesa #<?= htmlspecialchars($r['numero_mesa'] ?? '?') ?>
                                <span class="text-stone-400 font-normal text-base ml-2">
                                    · <?= (int)($r['capacidad'] ?? 0) ?> personas máx.
                                </span>
                            </p>
                            <div class="flex flex-wrap gap-4 mt-1.5 text-sm text-stone-500 font-semibold">
                                <span><i class="fa-solid fa-calendar text-orange-400 mr-1"></i><?= date('d/m/Y', strtotime($r['fecha_reserva'])) ?></span>
                                <span><i class="fa-solid fa-clock text-orange-400 mr-1"></i><?= substr($r['hora_reserva'], 0, 5) ?></span>
                                <span><i class="fa-solid fa-users text-orange-400 mr-1"></i><?= (int)$r['numero_personas'] ?> personas</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="badge text-sm <?= $badgeClass ?>">
                            <?= htmlspecialchars($estado) ?>
                        </span>

                        <?php if (!$cancelada): ?>
                            <form method="POST" onsubmit="return confirm('¿Cancelar esta reserva?')">
                                <input type="hidden" name="accion"     value="cancelar">
                                <input type="hidden" name="id_reserva" value="<?= (int)$r['id_reserva'] ?>">
                                <input type="hidden" name="id_mesa"    value="<?= (int)$r['id_mesa'] ?>">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 font-bold text-sm transition">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>


<script>
var mesaSeleccionada = null;

function seleccionarMesa(el) {
    if (el.classList.contains('bloqueada')) return;

    document.querySelectorAll('.mesa-card').forEach(function(c) {
        c.classList.remove('seleccionada');
    });

    el.classList.add('seleccionada');
    mesaSeleccionada = el.dataset.id;

    document.getElementById('inp_mesa').value = mesaSeleccionada;
    document.getElementById('mesa_seleccionada_nombre').textContent =
        'Mesa #' + el.dataset.numero + ' seleccionada — ' + el.dataset.capacidad + ' personas máx.';
    document.getElementById('mesa_seleccionada_txt').classList.remove('hidden');

    validarFormulario();
}

function filtrarMesasPorPersonas() {
    var personas = parseInt(document.getElementById('inp_personas').value) || 0;
    document.querySelectorAll('.mesa-card').forEach(function(c) {
        var cap    = parseInt(c.dataset.capacidad);
        var estado = c.dataset.estado;
        var bloq   = estado === 'ocupada' || estado === 'mantenimiento';
        if (!bloq && personas > 0 && cap < personas) {
            c.classList.add('insuficiente');
            c.title = 'Capacidad insuficiente para ' + personas + ' personas';
            // Deseleccionar si estaba seleccionada
            if (c.classList.contains('seleccionada')) {
                c.classList.remove('seleccionada');
                mesaSeleccionada = null;
                document.getElementById('inp_mesa').value = '';
                document.getElementById('mesa_seleccionada_txt').classList.add('hidden');
            }
        } else {
            c.classList.remove('insuficiente');
            c.title = '';
        }
    });
    validarFormulario();
}

function actualizarMesas() {
    mesaSeleccionada = null;
    document.getElementById('inp_mesa').value = '';
    document.getElementById('mesa_seleccionada_txt').classList.add('hidden');
    document.querySelectorAll('.mesa-card').forEach(function(c) {
        c.classList.remove('seleccionada');
    });
    validarFormulario();
}

function validarFormulario() {
    var fecha    = document.getElementById('inp_fecha').value;
    var hora     = document.getElementById('inp_hora').value;
    var personas = document.getElementById('inp_personas').value;
    document.getElementById('btn_reservar').disabled = !(fecha && hora && personas && mesaSeleccionada);
}

document.addEventListener('DOMContentLoaded', validarFormulario);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
