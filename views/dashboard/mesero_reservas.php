<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'mesero') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reserva.php';

$db           = (new Database())->conectar();
$reservaModel = new Reserva($db);

$mensaje = '';
$error   = '';

/* ── CAMBIAR ESTADO RESERVA ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
    $idReserva = (int)($_POST['id_reserva']        ?? 0);
    $idEstado  = (int)($_POST['id_estado_reserva'] ?? 0);

    if ($idReserva > 0 && $idEstado > 0) {
        $sql  = "UPDATE reserva SET id_estado_reserva = :est WHERE id_reserva = :id";
        $stmt = $db->prepare($sql);
        $ok   = $stmt->execute([':est' => $idEstado, ':id' => $idReserva]);
        $mensaje = $ok ? 'Estado de la reserva actualizado.' : 'Error al actualizar.';
    } else {
        $error = 'Datos inválidos.';
    }
}

// Obtener todas las reservas con joins
$sql = "SELECT
            r.id_reserva,
            r.fecha_reserva,
            r.hora_reserva,
            r.numero_personas,
            m.numero_mesa,
            m.capacidad,
            er.id_estado_reserva,
            er.nombre_estado,
            u.nombre AS cliente
        FROM reserva r
        LEFT JOIN mesa           m  ON r.id_mesa            = m.id_mesa
        LEFT JOIN estado_reserva er ON r.id_estado_reserva  = er.id_estado_reserva
        LEFT JOIN cliente        c  ON r.id_cliente         = c.id_cliente
        LEFT JOIN usuario        u  ON c.id_usuario         = u.id_usuario
        ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";

$stmt     = $db->prepare($sql);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contadores por estado
$counts = ['Pendiente' => 0, 'Confirmada' => 0, 'Cancelada' => 0];
foreach ($reservas as $r) {
    $e = $r['nombre_estado'] ?? '';
    if (isset($counts[$e])) $counts[$e]++;
}

$titulo = 'Reservas';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
.res-card { background:rgba(255,247,237,.95);border:1px solid rgba(251,146,60,.18);border-radius:20px;padding:1.5rem; }
.res-table { width:100%;border-collapse:collapse;font-size:.9rem; }
.res-table thead tr { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff; }
.res-table thead th { padding:.8rem 1rem;text-align:left;font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
.res-table tbody tr { border-bottom:1px solid #f5f0eb;transition:background .15s; }
.res-table tbody tr:hover { background:#fff7ed; }
.res-table tbody td { padding:.8rem 1rem; }
.badge-Pendiente  { background:#fef9c3;color:#a16207; }
.badge-Confirmada { background:#dcfce7;color:#15803d; }
.badge-Cancelada  { background:#fee2e2;color:#b91c1c; }
#modalEstadoRes { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center; }
@keyframes popIn { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }
</style>

<div class="space-y-6">

    <!-- ALERTAS -->
    <?php if ($mensaje): ?>
    <div style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;font-weight:700;">
        <i class="fa-solid fa-circle-check text-xl"></i> <?= htmlspecialchars($mensaje) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;font-weight:700;">
        <i class="fa-solid fa-circle-exclamation text-xl"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- ENCABEZADO -->
    <div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:24px;padding:1.5rem 2rem;">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-orange-300 font-bold uppercase tracking-widest text-xs mb-1">Panel Mesero</p>
                <h1 class="text-3xl font-black text-white">Reservas</h1>
                <p class="text-orange-200 text-sm mt-1"><?= count($reservas) ?> reserva(s) registrada(s)</p>
            </div>
            <!-- Mini stats -->
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <div style="background:rgba(254,249,195,.15);border-radius:14px;padding:.6rem 1rem;text-align:center;">
                    <p style="font-size:1.4rem;font-weight:900;color:#fbbf24;"><?= $counts['Pendiente'] ?></p>
                    <p style="font-size:.65rem;color:#fde68a;font-weight:700;text-transform:uppercase;">Pendientes</p>
                </div>
                <div style="background:rgba(220,252,231,.15);border-radius:14px;padding:.6rem 1rem;text-align:center;">
                    <p style="font-size:1.4rem;font-weight:900;color:#4ade80;"><?= $counts['Confirmada'] ?></p>
                    <p style="font-size:.65rem;color:#86efac;font-weight:700;text-transform:uppercase;">Confirmadas</p>
                </div>
                <div style="background:rgba(254,226,226,.15);border-radius:14px;padding:.6rem 1rem;text-align:center;">
                    <p style="font-size:1.4rem;font-weight:900;color:#f87171;"><?= $counts['Cancelada'] ?></p>
                    <p style="font-size:.65rem;color:#fca5a5;font-weight:700;text-transform:uppercase;">Canceladas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA -->
    <div class="res-card">

        <!-- Controles -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h2 style="font-size:1.2rem;font-weight:900;color:#1c1917;">Listado de Reservas</h2>
            </div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
                <!-- Filtro fecha -->
                <div>
                    <label style="font-size:.72rem;font-weight:700;color:#78716c;display:block;margin-bottom:.2rem;">Fecha</label>
                    <input type="date" id="filtro-fecha" onchange="filtrarReservas()"
                        style="padding:.45rem .85rem;border:2px solid #e7e5e4;border-radius:11px;font-size:.82rem;outline:none;"
                        onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                </div>
                <!-- Filtro estado -->
                <div>
                    <label style="font-size:.72rem;font-weight:700;color:#78716c;display:block;margin-bottom:.2rem;">Estado</label>
                    <select id="filtro-estado" onchange="filtrarReservas()"
                        style="padding:.45rem .85rem;border:2px solid #e7e5e4;border-radius:11px;font-size:.82rem;outline:none;background:#fff;cursor:pointer;"
                        onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                        <option value="">Todos</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
                <!-- Buscador -->
                <div>
                    <label style="font-size:.72rem;font-weight:700;color:#78716c;display:block;margin-bottom:.2rem;">Buscar</label>
                    <input type="text" id="filtro-buscar" placeholder="Cliente o mesa..." oninput="filtrarReservas()"
                        style="padding:.45rem .85rem;border:2px solid #e7e5e4;border-radius:11px;font-size:.82rem;outline:none;width:160px;"
                        onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                </div>
                <!-- Limpiar -->
                <div style="margin-top:1.1rem;">
                    <button onclick="limpiarFiltros()"
                        style="padding:.45rem .85rem;border:2px solid #e7e5e4;border-radius:11px;font-size:.82rem;background:#fff;color:#78716c;font-weight:700;cursor:pointer;transition:all .2s;"
                        onmouseover="this.style.borderColor='#ea580c';this.style.color='#ea580c'"
                        onmouseout="this.style.borderColor='#e7e5e4';this.style.color='#78716c'">
                        <i class="fa-solid fa-xmark mr-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($reservas)): ?>
        <div style="text-align:center;padding:3.5rem;color:#a8a29e;">
            <i class="fa-solid fa-calendar-xmark" style="font-size:3rem;display:block;margin-bottom:1rem;color:#d6d3d1;"></i>
            <p style="font-size:1.1rem;font-weight:700;">No hay reservas registradas</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="res-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Mesa</th>
                        <th>Capacidad</th>
                        <th>Personas</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-reservas">
                <?php foreach ($reservas as $r):
                    $estado     = $r['nombre_estado'] ?? 'Pendiente';
                    $estadoSlug = str_replace(' ', '-', $estado);
                    $fechaISO   = $r['fecha_reserva'] ?? '';
                    $fechaFmt   = $fechaISO ? date('d/m/Y', strtotime($fechaISO)) : '—';
                    $horaFmt    = $r['hora_reserva'] ? substr($r['hora_reserva'], 0, 5) : '—';
                    $esHoy      = $fechaISO === date('Y-m-d');
                    $esFutura   = $fechaISO > date('Y-m-d');
                ?>
                <tr class="fila-res"
                    data-estado="<?= htmlspecialchars($estado) ?>"
                    data-fecha="<?= htmlspecialchars($fechaISO) ?>"
                    data-texto="<?= strtolower('#'.$r['id_reserva'].' '.($r['cliente']??'').' mesa '.$r['numero_mesa'].' '.$estado) ?>">

                    <td><span style="font-family:monospace;font-weight:900;color:#ea580c;">#<?= (int)$r['id_reserva'] ?></span></td>

                    <td>
                        <div style="display:flex;align-items:center;gap:.4rem;">
                            <?php if ($esHoy): ?>
                                <span style="background:#dcfce7;color:#15803d;font-size:.65rem;font-weight:700;padding:.15rem .5rem;border-radius:999px;">HOY</span>
                            <?php elseif ($esFutura): ?>
                                <span style="background:#dbeafe;color:#1d4ed8;font-size:.65rem;font-weight:700;padding:.15rem .5rem;border-radius:999px;">PRÓXIMA</span>
                            <?php endif; ?>
                            <span style="font-weight:600;color:#1c1917;"><?= $fechaFmt ?></span>
                        </div>
                    </td>

                    <td>
                        <span style="display:inline-flex;align-items:center;gap:.3rem;font-weight:700;color:#78716c;">
                            <i class="fa-solid fa-clock text-orange-400" style="font-size:.8rem;"></i>
                            <?= $horaFmt ?>
                        </span>
                    </td>

                    <td>
                        <span style="font-weight:900;color:#ea580c;font-size:1rem;">
                            Mesa #<?= htmlspecialchars($r['numero_mesa'] ?? '?') ?>
                        </span>
                    </td>

                    <td style="text-align:center;color:#78716c;">
                        <i class="fa-solid fa-users text-orange-400 mr-1" style="font-size:.8rem;"></i>
                        <?= (int)($r['capacidad'] ?? 0) ?>
                    </td>

                    <td style="text-align:center;">
                        <span style="background:#fff7ed;color:#ea580c;font-weight:900;font-size:.85rem;padding:.2rem .65rem;border-radius:999px;">
                            <?= (int)$r['numero_personas'] ?>
                        </span>
                    </td>

                    <td style="font-weight:600;color:#1c1917;">
                        <?= htmlspecialchars($r['cliente'] ?? '—') ?>
                    </td>

                    <td>
                        <span class="badge-<?= $estadoSlug ?>"
                            style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;padding:.3rem .75rem;border-radius:999px;">
                            <?php if ($estado === 'Confirmada'): ?>
                                <i class="fa-solid fa-circle-check" style="font-size:.6rem;"></i>
                            <?php elseif ($estado === 'Cancelada'): ?>
                                <i class="fa-solid fa-ban" style="font-size:.6rem;"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-clock" style="font-size:.6rem;"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($estado) ?>
                        </span>
                    </td>

                    <td style="text-align:center;">
                        <?php if ($estado !== 'Cancelada'): ?>
                        <button onclick="abrirCambioEstado(<?= (int)$r['id_reserva'] ?>, <?= (int)$r['id_estado_reserva'] ?>, '<?= htmlspecialchars($estado) ?>')"
                            style="display:inline-flex;align-items:center;gap:.3rem;background:#dbeafe;border:1px solid #bfdbfe;color:#1d4ed8;font-weight:700;font-size:.75rem;padding:.4rem .8rem;border-radius:8px;cursor:pointer;transition:background .2s;"
                            onmouseover="this.style.background='#bfdbfe'" onmouseout="this.style.background='#dbeafe'">
                            <i class="fa-solid fa-arrows-rotate"></i> Estado
                        </button>
                        <?php else: ?>
                        <span style="font-size:.75rem;color:#a8a29e;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="sin-reservas" style="display:none;text-align:center;padding:2.5rem;color:#a8a29e;">
            <i class="fa-solid fa-magnifying-glass" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#d6d3d1;"></i>
            <p style="font-weight:700;">No se encontraron reservas con esos filtros</p>
        </div>
        <?php endif; ?>

    </div>
</div><!-- /space-y-6 -->


<!-- ══ MODAL CAMBIO ESTADO ══ -->
<div id="modalEstadoRes">
<div style="background:#fff;border-radius:22px;width:100%;max-width:380px;padding:1.75rem;box-shadow:0 30px 80px rgba(0,0,0,.3);animation:popIn .22s ease;">

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;">
        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-arrows-rotate"></i>
        </div>
        <div>
            <h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;">Cambiar Estado</h3>
            <p id="res-num" style="font-size:.78rem;color:#78716c;"></p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="accion"            value="cambiar_estado">
        <input type="hidden" name="id_reserva"        id="res-id">

        <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.5rem;">

            <label style="display:flex;align-items:center;gap:.75rem;padding:.8rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;transition:border-color .2s;"
                   onmouseover="this.style.borderColor='#a16207'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_reserva" value="1" style="accent-color:#a16207;">
                <span style="background:#fef9c3;color:#a16207;font-weight:700;font-size:.85rem;padding:.25rem .8rem;border-radius:999px;">
                    <i class="fa-solid fa-clock mr-1" style="font-size:.7rem;"></i> Pendiente
                </span>
            </label>

            <label style="display:flex;align-items:center;gap:.75rem;padding:.8rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;transition:border-color .2s;"
                   onmouseover="this.style.borderColor='#15803d'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_reserva" value="2" style="accent-color:#15803d;">
                <span style="background:#dcfce7;color:#15803d;font-weight:700;font-size:.85rem;padding:.25rem .8rem;border-radius:999px;">
                    <i class="fa-solid fa-circle-check mr-1" style="font-size:.7rem;"></i> Confirmada
                </span>
            </label>

            <label style="display:flex;align-items:center;gap:.75rem;padding:.8rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;transition:border-color .2s;"
                   onmouseover="this.style.borderColor='#b91c1c'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_reserva" value="3" style="accent-color:#b91c1c;">
                <span style="background:#fee2e2;color:#b91c1c;font-weight:700;font-size:.85rem;padding:.25rem .8rem;border-radius:999px;">
                    <i class="fa-solid fa-ban mr-1" style="font-size:.7rem;"></i> Cancelada
                </span>
            </label>

        </div>

        <div style="display:flex;gap:.6rem;">
            <button type="button" onclick="cerrarModal()"
                style="flex:1;padding:.75rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;transition:background .2s;"
                onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='#fff'">
                Cancelar
            </button>
            <button type="submit"
                style="flex:1;padding:.75rem;border-radius:12px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;cursor:pointer;box-shadow:0 4px 14px rgba(234,88,12,.3);">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar
            </button>
        </div>
    </form>
</div>
</div>


<script>
/* ── Modal ── */
function abrirCambioEstado(id, estadoActual, nombreEstado) {
    document.getElementById('res-id').value  = id;
    document.getElementById('res-num').textContent = 'Reserva #' + id + ' — Estado actual: ' + nombreEstado;

    // Marcar el radio del estado actual
    document.querySelectorAll('[name="id_estado_reserva"]').forEach(function(r) {
        r.checked = (parseInt(r.value) === estadoActual);
    });

    document.getElementById('modalEstadoRes').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function cerrarModal() {
    document.getElementById('modalEstadoRes').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('modalEstadoRes').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModal();
});

/* ── Filtros ── */
function filtrarReservas() {
    var fecha  = document.getElementById('filtro-fecha').value;
    var estado = document.getElementById('filtro-estado').value;
    var q      = document.getElementById('filtro-buscar').value.toLowerCase();
    var vis    = 0;

    document.querySelectorAll('.fila-res').forEach(function(f) {
        var fechaOk  = !fecha  || f.dataset.fecha   === fecha;
        var estadoOk = !estado || f.dataset.estado  === estado;
        var textoOk  = !q     || f.dataset.texto.includes(q);
        var mostrar  = fechaOk && estadoOk && textoOk;
        f.style.display = mostrar ? '' : 'none';
        if (mostrar) vis++;
    });

    var sinRes = document.getElementById('sin-reservas');
    if (sinRes) sinRes.style.display = vis === 0 ? 'block' : 'none';
}

function limpiarFiltros() {
    document.getElementById('filtro-fecha').value  = '';
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-buscar').value = '';
    filtrarReservas();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
