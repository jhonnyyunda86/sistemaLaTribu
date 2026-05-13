<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'cliente') {
    header('Location: ../usuarios/login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Reserva.php';

$db          = (new Database())->conectar();
$pedidoModel = new Pedido($db);
$resModel    = new Reserva($db);

$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
$idCliente = $resModel->obtenerIdCliente($idUsuario);

// Si no tiene registro de cliente aún, mostrar vacío
$pedidos = [];
$stats   = ['total_pedidos' => 0, 'total_gastado' => 0, 'pedido_mayor' => 0, 'ultimo_pedido' => null];

if ($idCliente) {
    $pedidos = $pedidoModel->historialCliente($idCliente);
    $stats   = $pedidoModel->statsCliente($idCliente);
}

// Ver detalle de un pedido específico
$detalle    = [];
$idDetalle  = (int)($_GET['pedido'] ?? 0);
if ($idDetalle > 0 && $idCliente) {
    $detalle = $pedidoModel->detallePedido($idDetalle, $idCliente);
}

$titulo = 'Historial de Compras';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
.hist-card {
    background: rgba(255,247,237,.95);
    border: 1px solid rgba(251,146,60,.18);
    border-radius: 22px;
    padding: 1.5rem;
}
.kpi-hist {
    background: #fff;
    border: 2px solid #f5f0eb;
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform .2s, box-shadow .2s;
}
.kpi-hist:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(234,88,12,.12); }
.kpi-ico-h { width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0; }

/* Tabla */
.hist-table { width:100%;border-collapse:collapse;font-size:.9rem; }
.hist-table thead tr { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff; }
.hist-table thead th { padding:.8rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
.hist-table tbody tr { border-bottom:1px solid #f5f0eb;transition:background .15s;cursor:pointer; }
.hist-table tbody tr:hover { background:#fff7ed; }
.hist-table tbody td { padding:.8rem 1rem; }

/* Badge estado */
.badge-Pendiente      { background:#fef9c3;color:#a16207; }
.badge-En-preparación { background:#dbeafe;color:#1d4ed8; }
.badge-Entregado      { background:#dcfce7;color:#15803d; }
.badge-Cancelado      { background:#fee2e2;color:#b91c1c; }

/* Modal detalle */
#modalDetalle { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center; }
.detalle-box {
    background:#fff;border-radius:24px;width:100%;max-width:560px;
    max-height:88vh;overflow-y:auto;padding:1.75rem;
    box-shadow:0 30px 80px rgba(0,0,0,.3);
    animation:popIn .22s ease;
}
@keyframes popIn { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }

/* Filtro búsqueda */
#filtro-hist:focus { border-color:#ea580c;box-shadow:0 0 0 3px rgba(234,88,12,.1); }
</style>

<!-- ══ ENCABEZADO ══ -->
<div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:24px;padding:1.5rem 2rem;margin-bottom:1.5rem;">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-orange-300 font-bold uppercase tracking-widest text-xs mb-1">Mi cuenta</p>
            <h1 class="text-3xl font-black text-white">Historial de Compras</h1>
            <p class="text-orange-200 text-sm mt-1">Todos tus pedidos y compras realizadas</p>
        </div>
        <a href="cliente_dashboard.php"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white font-black px-5 py-2.5 rounded-2xl transition shadow-lg text-sm flex-shrink-0">
            <i class="fa-solid fa-burger"></i> Ver menú
        </a>
    </div>
</div>

<!-- ══ KPI CARDS ══ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="kpi-hist">
        <div class="kpi-ico-h" style="background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;">
            <i class="fa-solid fa-bag-shopping"></i>
        </div>
        <div>
            <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Total pedidos</p>
            <p style="font-size:2rem;font-weight:900;color:#1c1917;"><?= (int)$stats['total_pedidos'] ?></p>
        </div>
    </div>

    <div class="kpi-hist">
        <div class="kpi-ico-h" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
        <div>
            <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Total gastado</p>
            <p style="font-size:1.6rem;font-weight:900;color:#1c1917;">$<?= number_format((float)$stats['total_gastado'], 0, ',', '.') ?></p>
        </div>
    </div>

    <div class="kpi-hist">
        <div class="kpi-ico-h" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;">
            <i class="fa-solid fa-arrow-trend-up"></i>
        </div>
        <div>
            <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Pedido mayor</p>
            <p style="font-size:1.6rem;font-weight:900;color:#1c1917;">$<?= number_format((float)$stats['pedido_mayor'], 0, ',', '.') ?></p>
        </div>
    </div>

    <div class="kpi-hist">
        <div class="kpi-ico-h" style="background:linear-gradient(135deg,#0ea5e9,#0369a1);color:#fff;">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div>
            <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Último pedido</p>
            <p style="font-size:1rem;font-weight:900;color:#1c1917;">
                <?= $stats['ultimo_pedido'] ? date('d/m/Y', strtotime($stats['ultimo_pedido'])) : '—' ?>
            </p>
        </div>
    </div>

</div>

<!-- ══ TABLA DE PEDIDOS ══ -->
<div class="hist-card">

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <h2 style="font-size:1.2rem;font-weight:900;color:#1c1917;">Mis Compras</h2>
                <p style="font-size:.8rem;color:#78716c;"><?= count($pedidos) ?> pedido(s) registrado(s)</p>
            </div>
        </div>

        <!-- Buscador + filtro estado -->
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
            <input type="text" id="filtro-hist" placeholder="Buscar pedido..."
                oninput="filtrarHistorial()"
                style="padding:.55rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.85rem;outline:none;width:180px;transition:border-color .2s,box-shadow .2s;">
            <select id="filtro-estado" onchange="filtrarHistorial()"
                style="padding:.55rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.85rem;outline:none;background:#fff;cursor:pointer;">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En preparación">En preparación</option>
                <option value="Entregado">Entregado</option>
                <option value="Cancelado">Cancelado</option>
            </select>
        </div>
    </div>

    <?php if (empty($pedidos)): ?>
        <div style="text-align:center;padding:4rem;color:#a8a29e;">
            <i class="fa-solid fa-bag-shopping" style="font-size:3rem;display:block;margin-bottom:1rem;color:#d6d3d1;"></i>
            <p style="font-size:1.1rem;font-weight:700;">Aún no tienes pedidos</p>
            <p style="font-size:.85rem;margin-top:.4rem;">Explora el menú y realiza tu primer pedido</p>
            <a href="cliente_dashboard.php"
               style="display:inline-flex;align-items:center;gap:.5rem;margin-top:1.25rem;padding:.7rem 1.5rem;border-radius:14px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;font-size:.9rem;text-decoration:none;box-shadow:0 4px 14px rgba(234,88,12,.3);">
                <i class="fa-solid fa-burger"></i> Ir al menú
            </a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="hist-table" id="tablaHistorial">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Productos</th>
                        <th>Método pago</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Detalle</th>
                    </tr>
                </thead>
                <tbody id="cuerpoHistorial">
                    <?php foreach ($pedidos as $p):
                        $estadoSlug = str_replace(' ', '-', $p['estado'] ?? 'Pendiente');
                        $estadoIcon = match($p['estado'] ?? '') {
                            'Entregado'      => 'fa-circle-check',
                            'En preparación' => 'fa-fire-flame-curved',
                            'Cancelado'      => 'fa-ban',
                            default          => 'fa-clock',
                        };
                        $tipoIcon = match($p['tipo'] ?? '') {
                            'Domicilio'   => 'fa-motorcycle',
                            'Para llevar' => 'fa-bag-shopping',
                            default       => 'fa-chair',
                        };
                    ?>
                    <tr class="fila-hist"
                        data-estado="<?= htmlspecialchars($p['estado'] ?? '') ?>"
                        data-texto="<?= strtolower('#'.$p['id_pedido'].' '.($p['tipo']??'').' '.($p['estado']??'').' '.($p['metodo_pago']??'')) ?>"
                        onclick="verDetalle(<?= (int)$p['id_pedido'] ?>)">

                        <td>
                            <span style="font-family:monospace;font-weight:900;color:#ea580c;font-size:.95rem;">#<?= (int)$p['id_pedido'] ?></span>
                        </td>
                        <td style="font-size:.85rem;color:#78716c;">
                            <?= isset($p['fecha_pedido']) ? date('d/m/Y', strtotime($p['fecha_pedido'])) : '—' ?>
                            <br>
                            <span style="font-size:.75rem;color:#a8a29e;">
                                <?= isset($p['fecha_pedido']) ? date('H:i', strtotime($p['fecha_pedido'])) : '' ?>
                            </span>
                        </td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;font-weight:700;color:#78716c;">
                                <i class="fa-solid <?= $tipoIcon ?> text-orange-500"></i>
                                <?= htmlspecialchars($p['tipo'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span style="background:#fff7ed;color:#ea580c;font-weight:900;font-size:.85rem;padding:.2rem .65rem;border-radius:999px;">
                                <?= (int)$p['num_productos'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['metodo_pago']): ?>
                                <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.8rem;font-weight:700;background:#f5f0eb;color:#78716c;padding:.25rem .65rem;border-radius:999px;">
                                    <i class="fa-solid fa-credit-card" style="font-size:.65rem;"></i>
                                    <?= htmlspecialchars($p['metodo_pago']) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#a8a29e;font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-<?= $estadoSlug ?>"
                                style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;padding:.3rem .75rem;border-radius:999px;">
                                <i class="fa-solid <?= $estadoIcon ?>" style="font-size:.6rem;"></i>
                                <?= htmlspecialchars($p['estado'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="text-align:right;font-weight:900;color:#ea580c;font-size:1rem;">
                            $<?= number_format((float)$p['total'], 0, ',', '.') ?>
                        </td>
                        <td style="text-align:center;">
                            <button onclick="event.stopPropagation();verDetalle(<?= (int)$p['id_pedido'] ?>)"
                                style="display:inline-flex;align-items:center;gap:.3rem;background:#fff7ed;border:1px solid #fed7aa;color:#ea580c;font-weight:700;font-size:.75rem;padding:.4rem .8rem;border-radius:8px;cursor:pointer;transition:background .2s;"
                                onmouseover="this.style.background='#ffedd5'" onmouseout="this.style.background='#fff7ed'">
                                <i class="fa-solid fa-eye"></i> Ver
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Sin resultados -->
        <div id="sinResultadosHist" style="display:none;text-align:center;padding:2.5rem;color:#a8a29e;">
            <i class="fa-solid fa-magnifying-glass" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#d6d3d1;"></i>
            <p style="font-weight:700;">No se encontraron pedidos</p>
        </div>
    <?php endif; ?>

</div>


<!-- ══ MODAL DETALLE DEL PEDIDO ══ -->
<div id="modalDetalle">
    <div class="detalle-box">

        <!-- Header modal -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 id="detalle-titulo" style="font-size:1.1rem;font-weight:900;color:#1c1917;">Detalle del Pedido</h3>
                    <p id="detalle-fecha" style="font-size:.78rem;color:#78716c;"></p>
                </div>
            </div>
            <button onclick="cerrarDetalle()"
                style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;"
                onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'"
                onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Info del pedido -->
        <div id="detalle-info" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem;"></div>

        <!-- Productos -->
        <div style="margin-bottom:1.25rem;">
            <p style="font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid #f5f0eb;">
                <i class="fa-solid fa-utensils text-orange-500 mr-1"></i> Productos del pedido
            </p>
            <div id="detalle-productos"></div>
        </div>

        <!-- Total -->
        <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;border-radius:14px;padding:1rem 1.25rem;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-weight:700;color:#78716c;">Total pagado</span>
            <span id="detalle-total" style="font-size:1.4rem;font-weight:900;color:#ea580c;"></span>
        </div>

        <button onclick="cerrarDetalle()"
            style="width:100%;margin-top:1rem;padding:.75rem;border-radius:14px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;font-size:.9rem;transition:background .2s;"
            onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='#fff'">
            Cerrar
        </button>
    </div>
</div>


<!-- Datos de pedidos para JS -->
<script>
var pedidosData = <?= json_encode(
    array_combine(
        array_column($pedidos, 'id_pedido'),
        $pedidos
    )
) ?>;

/* ══ Filtro ══ */
function filtrarHistorial() {
    var q      = document.getElementById('filtro-hist').value.toLowerCase();
    var estado = document.getElementById('filtro-estado').value;
    var filas  = document.querySelectorAll('.fila-hist');
    var vis    = 0;

    filas.forEach(function(f) {
        var textoOk  = f.dataset.texto.includes(q);
        var estadoOk = !estado || f.dataset.estado === estado;
        f.style.display = (textoOk && estadoOk) ? '' : 'none';
        if (textoOk && estadoOk) vis++;
    });

    var sinRes = document.getElementById('sinResultadosHist');
    if (sinRes) sinRes.style.display = vis === 0 ? 'block' : 'none';
}

/* ══ Modal detalle ══ */
function verDetalle(idPedido) {
    var p = pedidosData[idPedido];
    if (!p) return;

    // Título y fecha
    document.getElementById('detalle-titulo').textContent = 'Pedido #' + p.id_pedido;
    document.getElementById('detalle-fecha').textContent  =
        p.fecha_pedido ? new Date(p.fecha_pedido).toLocaleString('es-CO') : '';

    // Info chips
    var estadoColors = {
        'Pendiente':      '#fef9c3|#a16207',
        'En preparación': '#dbeafe|#1d4ed8',
        'Entregado':      '#dcfce7|#15803d',
        'Cancelado':      '#fee2e2|#b91c1c',
    };
    var ec = (estadoColors[p.estado] || '#f5f0eb|#78716c').split('|');

    document.getElementById('detalle-info').innerHTML =
        chip('fa-tag',         'Estado',       p.estado      || '—', ec[0], ec[1]) +
        chip('fa-motorcycle',  'Tipo',         p.tipo        || '—', '#fff7ed', '#ea580c') +
        chip('fa-credit-card', 'Método pago',  p.metodo_pago || '—', '#f5f0eb', '#78716c') +
        chip('fa-box',         'Productos',    p.num_productos + ' ítem(s)', '#f5f0eb', '#78716c');

    // Total
    document.getElementById('detalle-total').textContent =
        '$' + parseFloat(p.total || 0).toLocaleString('es-CO');

    // Productos — se cargan via fetch
    document.getElementById('detalle-productos').innerHTML =
        '<div style="text-align:center;padding:1.5rem;color:#a8a29e;">' +
        '<i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i>' +
        '</div>';

    document.getElementById('modalDetalle').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Cargar detalle real via fetch
    fetch('?pedido=' + idPedido)
        .then(function(r) { return r.text(); })
        .then(function() {
            // Usar datos ya embebidos en la página si están disponibles
            cargarProductosPedido(idPedido);
        });
}

function cargarProductosPedido(idPedido) {
    // Llamada AJAX al endpoint de detalle
    fetch('cliente_historial_detalle.php?pedido=' + idPedido)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data || !data.productos) {
                document.getElementById('detalle-productos').innerHTML =
                    '<p style="color:#a8a29e;text-align:center;padding:1rem;">Sin productos registrados.</p>';
                return;
            }
            var html = '';
            data.productos.forEach(function(prod) {
                html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid #f5f0eb;">' +
                    '<div style="display:flex;align-items:center;gap:.6rem;">' +
                        '<div style="width:32px;height:32px;border-radius:9px;background:#fff7ed;border:1px solid #fed7aa;display:flex;align-items:center;justify-content:center;color:#ea580c;font-size:.8rem;flex-shrink:0;">' +
                            '<i class="fa-solid fa-utensils"></i>' +
                        '</div>' +
                        '<div>' +
                            '<p style="font-weight:700;font-size:.88rem;color:#1c1917;">' + prod.nombre + '</p>' +
                            '<p style="font-size:.75rem;color:#a8a29e;">x' + prod.cantidad + ' · $' + parseFloat(prod.precio_unitario).toLocaleString('es-CO') + ' c/u</p>' +
                        '</div>' +
                    '</div>' +
                    '<span style="font-weight:900;color:#ea580c;font-size:.9rem;">$' + parseFloat(prod.subtotal).toLocaleString('es-CO') + '</span>' +
                '</div>';
            });
            document.getElementById('detalle-productos').innerHTML = html;
        })
        .catch(function() {
            document.getElementById('detalle-productos').innerHTML =
                '<p style="color:#a8a29e;text-align:center;padding:1rem;">No se pudo cargar el detalle.</p>';
        });
}

function chip(icon, label, value, bg, color) {
    return '<div style="background:' + bg + ';border-radius:12px;padding:.65rem .9rem;">' +
        '<p style="font-size:.7rem;font-weight:700;color:#a8a29e;text-transform:uppercase;margin-bottom:.2rem;">' +
            '<i class="fa-solid ' + icon + '" style="color:' + color + ';margin-right:.3rem;"></i>' + label +
        '</p>' +
        '<p style="font-weight:900;font-size:.9rem;color:' + color + ';">' + value + '</p>' +
    '</div>';
}

function cerrarDetalle() {
    document.getElementById('modalDetalle').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('modalDetalle').addEventListener('click', function(e) {
    if (e.target === this) cerrarDetalle();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarDetalle();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
