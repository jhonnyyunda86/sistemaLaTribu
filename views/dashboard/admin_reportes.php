<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reporte.php';

$db     = (new Database())->conectar();
$model  = new Reporte($db);

// ── Rango seleccionado ──────────────────────────────────────────────────────
$periodo = $_GET['periodo'] ?? 'hoy';
$hoy     = date('Y-m-d');

switch ($periodo) {
    case 'semana':
        $desde = date('Y-m-d', strtotime('monday this week'));
        $hasta = date('Y-m-d', strtotime('sunday this week'));
        $labelPeriodo = 'Esta semana (' . date('d/m', strtotime($desde)) . ' - ' . date('d/m/Y', strtotime($hasta)) . ')';
        break;
    case 'mes':
        $desde = date('Y-m-01');
        $hasta = date('Y-m-t');
        $labelPeriodo = 'Este mes (' . date('F Y') . ')';
        break;
    case 'personalizado':
        $desde = $_GET['desde'] ?? $hoy;
        $hasta = $_GET['hasta'] ?? $hoy;
        $labelPeriodo = 'Del ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta));
        break;
    default: // hoy
        $desde = $hoy;
        $hasta = $hoy;
        $labelPeriodo = 'Hoy (' . date('d/m/Y') . ')';
        break;
}

$resumen   = $model->resumen($desde, $hasta);
$ventas    = $model->ventasPorRango($desde, $hasta);
$pedidos   = $model->pedidosPorRango($desde, $hasta);
$reservas  = $model->reservasPorRango($desde, $hasta);
$topProd   = $model->productosMasVendidos($desde, $hasta);
$ventasDia = $model->ventasPorDia($desde, $hasta);

$titulo = 'Reportes';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<style>
.rep-card { background:rgba(255,247,237,.95); border:1px solid rgba(251,146,60,.2); border-radius:24px; padding:1.75rem; }
.kpi-icon { width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0; }
.tab-btn  { padding:.6rem 1.4rem;border-radius:12px;font-weight:700;font-size:.9rem;transition:all .2s;border:2px solid transparent; }
.tab-btn.activo { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;box-shadow:0 6px 18px rgba(234,88,12,.3); }
.tab-btn:not(.activo) { background:#fff;color:#78716c;border-color:#e7e5e4; }
.tab-btn:not(.activo):hover { border-color:#fdba74;color:#ea580c; }
table.rep-table { width:100%;border-collapse:collapse;font-size:.92rem; }
table.rep-table thead tr { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff; }
table.rep-table thead th { padding:.85rem 1rem;text-align:left;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em; }
table.rep-table tbody tr { border-bottom:1px solid #f5f0eb;transition:background .15s; }
table.rep-table tbody tr:hover { background:#fff7ed; }
table.rep-table tbody td { padding:.8rem 1rem; }
.bar-wrap { background:#f5f0eb;border-radius:999px;height:10px;overflow:hidden; }
.bar-fill  { height:100%;border-radius:999px;background:linear-gradient(90deg,#ea580c,#f59e0b); }
</style>

<div id="paginaReporte" class="space-y-7">

    <!-- ══ ENCABEZADO ══ -->
    <div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:28px;padding:2rem;">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
            <div>
                <p class="text-orange-300 font-bold uppercase tracking-widest text-sm mb-1">Panel administrativo</p>
                <h1 class="text-4xl font-black text-white">Reportes</h1>
                <p class="text-orange-200 mt-1 text-base"><?= htmlspecialchars($labelPeriodo) ?></p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button onclick="generarPDF('descargar')" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-black px-5 py-3 rounded-2xl shadow-lg transition text-sm">
                    <i class="fa-solid fa-file-pdf text-lg"></i> Descargar PDF
                </button>
                <button onclick="generarPDF('imprimir')" class="inline-flex items-center gap-2 bg-stone-700 hover:bg-stone-600 text-white font-black px-5 py-3 rounded-2xl shadow-lg transition text-sm">
                    <i class="fa-solid fa-print text-lg"></i> Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- ══ FILTROS DE PERIODO ══ -->
    <div class="rep-card">
        <p class="text-xs font-black text-stone-500 uppercase tracking-widest mb-4"><i class="fa-solid fa-filter text-orange-500 mr-1"></i> Filtrar por periodo</p>
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <a href="?periodo=hoy"    class="tab-btn <?= $periodo==='hoy'    ?'activo':'' ?>"><i class="fa-solid fa-sun mr-1"></i> Hoy</a>
            <a href="?periodo=semana" class="tab-btn <?= $periodo==='semana' ?'activo':'' ?>"><i class="fa-solid fa-calendar-week mr-1"></i> Esta semana</a>
            <a href="?periodo=mes"    class="tab-btn <?= $periodo==='mes'    ?'activo':'' ?>"><i class="fa-solid fa-calendar mr-1"></i> Este mes</a>
            <div class="flex items-end gap-2 ml-2">
                <div>
                    <label class="block text-xs font-bold text-stone-500 mb-1">Desde</label>
                    <input type="date" name="desde" value="<?= htmlspecialchars($periodo==='personalizado'?$desde:date('Y-m-01')) ?>"
                        class="p-2.5 border-2 border-stone-200 rounded-xl text-sm focus:outline-none focus:border-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-stone-500 mb-1">Hasta</label>
                    <input type="date" name="hasta" value="<?= htmlspecialchars($periodo==='personalizado'?$hasta:date('Y-m-d')) ?>"
                        class="p-2.5 border-2 border-stone-200 rounded-xl text-sm focus:outline-none focus:border-orange-400">
                </div>
                <input type="hidden" name="periodo" value="personalizado">
                <button type="submit" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-black rounded-xl text-sm transition">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- ══ KPIs ══ -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <?php
        $kpis = [
            ['Total Ventas',    '$'.number_format($resumen['total_ventas'],2),    'fa-dollar-sign',  'from-orange-500 to-amber-400',  'bg-orange-50 text-orange-600'],
            ['Facturas',        $resumen['num_facturas'],                          'fa-receipt',      'from-green-500 to-emerald-400', 'bg-green-50 text-green-600'],
            ['Pedidos',         $resumen['num_pedidos'],                           'fa-bag-shopping', 'from-blue-500 to-cyan-400',     'bg-blue-50 text-blue-600'],
            ['Reservas',        $resumen['num_reservas'],                          'fa-calendar-check','from-purple-500 to-violet-400','bg-purple-50 text-purple-600'],
            ['Ticket Promedio', '$'.number_format($resumen['ticket_promedio'],2),  'fa-chart-line',   'from-rose-500 to-pink-400',     'bg-rose-50 text-rose-600'],
        ];
        foreach ($kpis as $k): ?>
        <div class="rep-card flex items-center gap-4 hover:-translate-y-1 transition duration-200">
            <div class="kpi-icon bg-gradient-to-br <?= $k[3] ?> text-white shadow-lg">
                <i class="fa-solid <?= $k[2] ?>"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-stone-400 uppercase tracking-wide"><?= $k[0] ?></p>
                <p class="text-2xl font-black text-stone-900 mt-0.5"><?= $k[1] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ══ GRÁFICA + TOP PRODUCTOS ══ -->
    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Gráfica ventas por día -->
        <div class="rep-card">
            <h3 class="text-lg font-black text-stone-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-bar text-orange-500"></i> Ventas por día
            </h3>
            <?php if (empty($ventasDia)): ?>
                <p class="text-stone-400 text-center py-8">Sin datos en este periodo</p>
            <?php else:
                $maxTotal = max(array_column($ventasDia, 'total')) ?: 1;
            ?>
            <div class="space-y-3">
                <?php foreach ($ventasDia as $vd):
                    $pct = round(($vd['total'] / $maxTotal) * 100);
                ?>
                <div>
                    <div class="flex justify-between text-sm font-semibold text-stone-600 mb-1">
                        <span><?= date('d/m/Y', strtotime($vd['fecha'])) ?></span>
                        <span class="text-orange-600 font-black">$<?= number_format($vd['total'],2) ?></span>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top productos -->
        <div class="rep-card">
            <h3 class="text-lg font-black text-stone-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-amber-500"></i> Productos más vendidos
            </h3>
            <?php if (empty($topProd)): ?>
                <p class="text-stone-400 text-center py-8">Sin datos en este periodo</p>
            <?php else:
                $maxUnid = max(array_column($topProd, 'total_unidades')) ?: 1;
            ?>
            <div class="space-y-4">
                <?php foreach ($topProd as $i => $tp): ?>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-sm
                        <?= $i===0?'bg-amber-400 text-white':($i===1?'bg-stone-300 text-stone-700':'bg-orange-100 text-orange-600') ?>">
                        <?= $i+1 ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm font-bold text-stone-700 mb-1">
                            <span><?= htmlspecialchars($tp['nombre']) ?></span>
                            <span class="text-orange-600"><?= (int)$tp['total_unidades'] ?> uds.</span>
                        </div>
                        <div class="bar-wrap">
                            <div class="bar-fill" style="width:<?= round(($tp['total_unidades']/$maxUnid)*100) ?>%"></div>
                        </div>
                    </div>
                    <span class="text-xs font-black text-stone-500 w-24 text-right">$<?= number_format($tp['total_ingresos'],0) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ TABLA VENTAS / FACTURAS ══ -->
    <div class="rep-card">
        <h3 class="text-lg font-black text-stone-900 mb-5 flex items-center gap-2">
            <i class="fa-solid fa-receipt text-orange-500"></i>
            Facturas / Pagos completados
            <span class="ml-auto text-sm font-bold text-stone-400"><?= count($ventas) ?> registros</span>
        </h3>
        <?php if (empty($ventas)): ?>
            <p class="text-stone-400 text-center py-8">Sin facturas en este periodo</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="rep-table">
                <thead><tr>
                    <th>#</th><th>Fecha</th><th>Cliente</th><th>Pedido</th><th>Método pago</th><th class="text-right">Total</th>
                </tr></thead>
                <tbody>
                <?php foreach ($ventas as $v): ?>
                <tr>
                    <td class="font-mono text-stone-400"><?= (int)$v['id_factura'] ?></td>
                    <td><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                    <td class="font-semibold"><?= htmlspecialchars($v['cliente'] ?? '—') ?></td>
                    <td><span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">#<?= (int)$v['id_pedido'] ?></span></td>
                    <td>
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">
                            <i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars($v['metodo_pago'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="text-right font-black text-orange-600">$<?= number_format((float)$v['total_factura'],2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-orange-200">
                        <td colspan="5" class="pt-3 font-black text-stone-700 text-right pr-4">TOTAL</td>
                        <td class="pt-3 text-right font-black text-xl text-orange-600">$<?= number_format($resumen['total_ventas'],2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ TABLA PEDIDOS ══ -->
    <div class="rep-card">
        <h3 class="text-lg font-black text-stone-900 mb-5 flex items-center gap-2">
            <i class="fa-solid fa-bag-shopping text-blue-500"></i>
            Pedidos del periodo
            <span class="ml-auto text-sm font-bold text-stone-400"><?= count($pedidos) ?> registros</span>
        </h3>
        <?php if (empty($pedidos)): ?>
            <p class="text-stone-400 text-center py-8">Sin pedidos en este periodo</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="rep-table">
                <thead><tr>
                    <th>#</th><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Estado</th><th class="text-right">Total</th>
                </tr></thead>
                <tbody>
                <?php foreach ($pedidos as $p):
                    $estadoClass = match($p['estado'] ?? '') {
                        'Entregado'      => 'bg-green-100 text-green-700',
                        'En preparación' => 'bg-blue-100 text-blue-700',
                        'Cancelado'      => 'bg-red-100 text-red-700',
                        default          => 'bg-amber-100 text-amber-700',
                    };
                ?>
                <tr>
                    <td class="font-mono text-stone-400"><?= (int)$p['id_pedido'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
                    <td class="font-semibold"><?= htmlspecialchars($p['cliente'] ?? '—') ?></td>
                    <td><span class="text-xs font-bold px-2 py-0.5 rounded-full bg-stone-100 text-stone-600"><?= htmlspecialchars($p['tipo'] ?? '—') ?></span></td>
                    <td><span class="text-xs font-bold px-2 py-0.5 rounded-full <?= $estadoClass ?>"><?= htmlspecialchars($p['estado'] ?? '—') ?></span></td>
                    <td class="text-right font-black text-stone-700">$<?= number_format((float)$p['total'],2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ TABLA RESERVAS ══ -->
    <div class="rep-card">
        <h3 class="text-lg font-black text-stone-900 mb-5 flex items-center gap-2">
            <i class="fa-solid fa-calendar-check text-purple-500"></i>
            Reservas del periodo
            <span class="ml-auto text-sm font-bold text-stone-400"><?= count($reservas) ?> registros</span>
        </h3>
        <?php if (empty($reservas)): ?>
            <p class="text-stone-400 text-center py-8">Sin reservas en este periodo</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="rep-table">
                <thead><tr>
                    <th>#</th><th>Fecha</th><th>Hora</th><th>Mesa</th><th>Personas</th><th>Cliente</th><th>Estado</th>
                </tr></thead>
                <tbody>
                <?php foreach ($reservas as $r):
                    $estClass = match($r['estado'] ?? '') {
                        'Confirmada' => 'bg-green-100 text-green-700',
                        'Cancelada'  => 'bg-red-100 text-red-700',
                        default      => 'bg-amber-100 text-amber-700',
                    };
                ?>
                <tr>
                    <td class="font-mono text-stone-400"><?= (int)$r['id_reserva'] ?></td>
                    <td><?= date('d/m/Y', strtotime($r['fecha_reserva'])) ?></td>
                    <td><?= substr($r['hora_reserva'],0,5) ?></td>
                    <td><span class="font-black text-orange-600">Mesa #<?= (int)$r['numero_mesa'] ?></span></td>
                    <td class="text-center"><?= (int)$r['numero_personas'] ?></td>
                    <td class="font-semibold"><?= htmlspecialchars($r['cliente'] ?? '—') ?></td>
                    <td><span class="text-xs font-bold px-2 py-0.5 rounded-full <?= $estClass ?>"><?= htmlspecialchars($r['estado'] ?? '—') ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /paginaReporte -->

<!-- jsPDF + autoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<style>
@media print {
    #sidebar, #top-header, #bottom-footer, .no-print { display:none !important; }
    #app, #col-right, #content-area { display:block !important; height:auto !important; overflow:visible !important; }
    body { background:#fff !important; overflow:auto !important; }
    .rep-card { border:1px solid #ddd !important; background:#fff !important; box-shadow:none !important; }
}
</style>

<script>
function generarPDF(modo) {
    modo = modo || 'descargar';
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const naranja = [234, 88, 12];
    const ambar   = [245, 158, 11];
    const oscuro  = [28, 25, 23];
    const W = doc.internal.pageSize.getWidth();

    // ── Encabezado ──────────────────────────────────────────────
    doc.setFillColor(...naranja);
    doc.rect(0, 0, W, 32, 'F');
    doc.setFillColor(...ambar);
    doc.rect(W - 40, 0, 40, 32, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(22);
    doc.setFont('helvetica', 'bold');
    doc.text('La Tribu', 14, 13);

    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Sistema de Gestion de Restaurante', 14, 20);
    doc.text('Reporte generado: ' + new Date().toLocaleString('es-CO'), 14, 27);

    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('REPORTE DE VENTAS', W - 38, 13, { align: 'center' });
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text('<?= addslashes($labelPeriodo) ?>', W - 38, 20, { align: 'center' });

    let y = 40;

    // ── KPIs ────────────────────────────────────────────────────
    doc.setTextColor(...oscuro);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('Resumen del periodo', 14, y);
    y += 6;

    const kpis = [
        ['Total Ventas',    '<?= addslashes("$".number_format($resumen["total_ventas"],2)) ?>'],
        ['Facturas',        '<?= $resumen["num_facturas"] ?>'],
        ['Pedidos',         '<?= $resumen["num_pedidos"] ?>'],
        ['Reservas',        '<?= $resumen["num_reservas"] ?>'],
        ['Ticket Promedio', '<?= addslashes("$".number_format($resumen["ticket_promedio"],2)) ?>'],
    ];

    doc.autoTable({
        startY: y,
        head: [['Indicador', 'Valor']],
        body: kpis,
        theme: 'grid',
        headStyles: { fillColor: naranja, textColor: 255, fontStyle: 'bold', fontSize: 9 },
        bodyStyles: { fontSize: 9 },
        alternateRowStyles: { fillColor: [255, 247, 237] },
        columnStyles: { 1: { fontStyle: 'bold', textColor: naranja } },
        margin: { left: 14, right: 14 },
    });
    y = doc.lastAutoTable.finalY + 10;

    // ── Facturas ────────────────────────────────────────────────
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(...oscuro);
    doc.text('Facturas / Pagos completados', 14, y);
    y += 4;

    const ventasRows = <?= json_encode(array_map(fn($v) => [
        $v['id_factura'],
        date('d/m/Y', strtotime($v['fecha'])),
        $v['cliente'] ?? '—',
        '#'.$v['id_pedido'],
        $v['metodo_pago'] ?? '—',
        '$'.number_format((float)$v['total_factura'],2),
    ], $ventas)) ?>;

    doc.autoTable({
        startY: y,
        head: [['#', 'Fecha', 'Cliente', 'Pedido', 'Metodo', 'Total']],
        body: ventasRows.length ? ventasRows : [['Sin registros','','','','','']],
        theme: 'striped',
        headStyles: { fillColor: naranja, textColor: 255, fontStyle: 'bold', fontSize: 8 },
        bodyStyles: { fontSize: 8 },
        alternateRowStyles: { fillColor: [255, 247, 237] },
        columnStyles: { 5: { fontStyle: 'bold', textColor: naranja, halign: 'right' } },
        margin: { left: 14, right: 14 },
        foot: [['', '', '', '', 'TOTAL', '<?= addslashes("$".number_format($resumen["total_ventas"],2)) ?>']],
        footStyles: { fillColor: [255, 237, 213], textColor: naranja, fontStyle: 'bold', fontSize: 9 },
    });
    y = doc.lastAutoTable.finalY + 10;

    // ── Pedidos ─────────────────────────────────────────────────
    if (y > 240) { doc.addPage(); y = 20; }
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(...oscuro);
    doc.text('Pedidos del periodo', 14, y);
    y += 4;

    const pedidosRows = <?= json_encode(array_map(fn($p) => [
        $p['id_pedido'],
        date('d/m/Y H:i', strtotime($p['fecha_pedido'])),
        $p['cliente'] ?? '—',
        $p['tipo']    ?? '—',
        $p['estado']  ?? '—',
        '$'.number_format((float)$p['total'],2),
    ], $pedidos)) ?>;

    doc.autoTable({
        startY: y,
        head: [['#', 'Fecha', 'Cliente', 'Tipo', 'Estado', 'Total']],
        body: pedidosRows.length ? pedidosRows : [['Sin registros','','','','','']],
        theme: 'striped',
        headStyles: { fillColor: [59,130,246], textColor: 255, fontStyle: 'bold', fontSize: 8 },
        bodyStyles: { fontSize: 8 },
        alternateRowStyles: { fillColor: [239, 246, 255] },
        margin: { left: 14, right: 14 },
    });
    y = doc.lastAutoTable.finalY + 10;

    // ── Reservas ────────────────────────────────────────────────
    if (y > 240) { doc.addPage(); y = 20; }
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(...oscuro);
    doc.text('Reservas del periodo', 14, y);
    y += 4;

    const reservasRows = <?= json_encode(array_map(fn($r) => [
        $r['id_reserva'],
        date('d/m/Y', strtotime($r['fecha_reserva'])),
        substr($r['hora_reserva'],0,5),
        'Mesa #'.$r['numero_mesa'],
        $r['numero_personas'],
        $r['cliente'] ?? '—',
        $r['estado']  ?? '—',
    ], $reservas)) ?>;

    doc.autoTable({
        startY: y,
        head: [['#', 'Fecha', 'Hora', 'Mesa', 'Personas', 'Cliente', 'Estado']],
        body: reservasRows.length ? reservasRows : [['Sin registros','','','','','','']],
        theme: 'striped',
        headStyles: { fillColor: [139,92,246], textColor: 255, fontStyle: 'bold', fontSize: 8 },
        bodyStyles: { fontSize: 8 },
        alternateRowStyles: { fillColor: [245, 243, 255] },
        margin: { left: 14, right: 14 },
    });

    // ── Top productos ────────────────────────────────────────────
    y = doc.lastAutoTable.finalY + 10;
    if (y > 240) { doc.addPage(); y = 20; }
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(...oscuro);
    doc.text('Productos mas vendidos', 14, y);
    y += 4;

    const topRows = <?= json_encode(array_map(fn($t) => [
        $t['nombre'],
        $t['total_unidades'],
        '$'.number_format((float)$t['total_ingresos'],2),
    ], $topProd)) ?>;

    doc.autoTable({
        startY: y,
        head: [['Producto', 'Unidades vendidas', 'Ingresos']],
        body: topRows.length ? topRows : [['Sin registros','','']],
        theme: 'striped',
        headStyles: { fillColor: ambar, textColor: 255, fontStyle: 'bold', fontSize: 8 },
        bodyStyles: { fontSize: 8 },
        alternateRowStyles: { fillColor: [255, 251, 235] },
        columnStyles: { 2: { fontStyle: 'bold', textColor: naranja, halign: 'right' } },
        margin: { left: 14, right: 14 },
    });

    // ── Pie de página ────────────────────────────────────────────
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150);
        doc.text('La Tribu — Reporte generado el ' + new Date().toLocaleString('es-CO'), 14, doc.internal.pageSize.getHeight() - 8);
        doc.text('Pagina ' + i + ' de ' + totalPages, W - 14, doc.internal.pageSize.getHeight() - 8, { align: 'right' });
    }

    const nombreArchivo = 'reporte-latribu-<?= $periodo ?>-<?= date("Ymd") ?>.pdf';

    if (modo === 'imprimir') {
        // Abrir el PDF en nueva pestaña y lanzar el diálogo de impresión
        const blob    = doc.output('blob');
        const blobUrl = URL.createObjectURL(blob);
        const ventana = window.open(blobUrl, '_blank');
        if (ventana) {
            ventana.addEventListener('load', function () {
                ventana.focus();
                ventana.print();
            });
        } else {
            // Si el navegador bloqueó el popup, descarga como fallback
            doc.save(nombreArchivo);
            alert('El navegador bloqueó la ventana emergente. Se descargó el PDF en su lugar.');
        }
    } else {
        doc.save(nombreArchivo);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
