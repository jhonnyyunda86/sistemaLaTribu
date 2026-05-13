<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'mesero') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';

$db        = (new Database())->conectar();
$prodModel = new Producto($db);
$productos = $prodModel->obtenerMenuCliente();

// Calcular resumen
$totalProds  = count($productos);
$agotados    = count(array_filter($productos, fn($p) => $p['estado_stock'] === 'agotado'));
$stockBajo   = count(array_filter($productos, fn($p) => $p['estado_stock'] === 'bajo'));
$disponibles = count(array_filter($productos, fn($p) => $p['estado_stock'] === 'disponible'));

$titulo = 'Stock de Productos';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- ENCABEZADO -->
    <div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:24px;padding:1.5rem 2rem;">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-orange-300 font-bold uppercase tracking-widest text-xs mb-1">Panel Mesero</p>
                <h1 class="text-3xl font-black text-white">Stock de Productos</h1>
                <p class="text-orange-200 text-sm mt-1">Disponibilidad actual del inventario — actualizado en tiempo real</p>
            </div>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <div style="background:rgba(255,255,255,.08);border-radius:16px;padding:.75rem 1.25rem;text-align:center;">
                    <p style="font-size:1.6rem;font-weight:900;color:#fff;"><?= $totalProds ?></p>
                    <p style="font-size:.7rem;color:#fdba74;font-weight:700;text-transform:uppercase;">Productos</p>
                </div>
                <div style="background:rgba(34,197,94,.15);border-radius:16px;padding:.75rem 1.25rem;text-align:center;">
                    <p style="font-size:1.6rem;font-weight:900;color:#4ade80;"><?= $disponibles ?></p>
                    <p style="font-size:.7rem;color:#86efac;font-weight:700;text-transform:uppercase;">Disponibles</p>
                </div>
                <div style="background:rgba(245,158,11,.15);border-radius:16px;padding:.75rem 1.25rem;text-align:center;">
                    <p style="font-size:1.6rem;font-weight:900;color:#fbbf24;"><?= $stockBajo ?></p>
                    <p style="font-size:.7rem;color:#fde68a;font-weight:700;text-transform:uppercase;">Stock bajo</p>
                </div>
                <div style="background:rgba(239,68,68,.15);border-radius:16px;padding:.75rem 1.25rem;text-align:center;">
                    <p style="font-size:1.6rem;font-weight:900;color:#f87171;"><?= $agotados ?></p>
                    <p style="font-size:.7rem;color:#fca5a5;font-weight:700;text-transform:uppercase;">Agotados</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTAS si hay agotados o stock bajo -->
    <?php if ($agotados > 0): ?>
    <div style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;font-weight:700;">
        <i class="fa-solid fa-triangle-exclamation text-xl flex-shrink-0"></i>
        <span><?= $agotados ?> producto(s) agotado(s). Informa al administrador para reabastecer.</span>
    </div>
    <?php endif; ?>
    <?php if ($stockBajo > 0): ?>
    <div style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;font-weight:700;">
        <i class="fa-solid fa-arrow-trend-down text-xl flex-shrink-0"></i>
        <span><?= $stockBajo ?> producto(s) con stock bajo. Considera avisar antes de que se agoten.</span>
    </div>
    <?php endif; ?>

    <!-- FILTROS + BUSCADOR -->
    <div style="background:rgba(255,247,237,.95);border:1px solid rgba(251,146,60,.18);border-radius:20px;padding:1.25rem 1.5rem;">
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
            <button onclick="filtrarStock('todos')" id="stk-todos"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:999px;border:2px solid transparent;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:700;font-size:.83rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-border-all"></i> Todos <span style="background:rgba(255,255,255,.25);border-radius:999px;padding:.05rem .45rem;font-size:.72rem;"><?= $totalProds ?></span>
            </button>
            <button onclick="filtrarStock('disponible')" id="stk-disponible"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;font-size:.83rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Disponible <span style="background:#f5f0eb;border-radius:999px;padding:.05rem .45rem;font-size:.72rem;"><?= $disponibles ?></span>
            </button>
            <button onclick="filtrarStock('bajo')" id="stk-bajo"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;font-size:.83rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;"></i> Stock bajo <span style="background:#f5f0eb;border-radius:999px;padding:.05rem .45rem;font-size:.72rem;"><?= $stockBajo ?></span>
            </button>
            <button onclick="filtrarStock('agotado')" id="stk-agotado"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;font-size:.83rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Agotado <span style="background:#f5f0eb;border-radius:999px;padding:.05rem .45rem;font-size:.72rem;"><?= $agotados ?></span>
            </button>
            <div style="position:relative;margin-left:auto;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#a8a29e;pointer-events:none;font-size:.85rem;"></i>
                <input type="text" id="stk-buscar" placeholder="Buscar producto..." oninput="buscarStock()"
                    style="padding:.5rem .9rem .5rem 2.4rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.85rem;outline:none;width:200px;transition:border-color .2s;"
                    onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
            </div>
        </div>
    </div>

    <!-- GRID DE PRODUCTOS -->
    <div id="grid-stock" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem;">
        <?php foreach ($productos as $prod):
            $stock  = (int)$prod['stock'];
            $minimo = (int)($prod['stock_minimo'] ?? 5);
            $estado = $prod['estado_stock'] ?? 'disponible';
            $pct    = $minimo > 0 ? min(100, round(($stock / ($minimo * 2)) * 100)) : ($stock > 0 ? 100 : 0);

            $barColor   = match($estado) { 'agotado'=>'#ef4444','bajo'=>'#f59e0b',default=>'#22c55e' };
            $bgCard     = match($estado) { 'agotado'=>'#fff1f2','bajo'=>'#fffbeb',default=>'#f0fdf4' };
            $borderCard = match($estado) { 'agotado'=>'#fecaca','bajo'=>'#fde68a',default=>'#bbf7d0' };
            $badgeBg    = match($estado) { 'agotado'=>'#fee2e2','bajo'=>'#fef9c3',default=>'#dcfce7' };
            $badgeColor = match($estado) { 'agotado'=>'#b91c1c','bajo'=>'#a16207',default=>'#15803d' };
            $badgeLabel = match($estado) { 'agotado'=>'Sin stock','bajo'=>'Stock bajo',default=>'Disponible' };
            $badgeIcon  = match($estado) { 'agotado'=>'fa-circle-xmark','bajo'=>'fa-triangle-exclamation',default=>'fa-circle-check' };
        ?>
        <div class="stk-card"
             data-estado="<?= $estado ?>"
             data-nombre="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>"
             style="background:<?= $bgCard ?>;border:2px solid <?= $borderCard ?>;border-radius:18px;padding:1.1rem;transition:transform .2s,box-shadow .2s;"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 28px rgba(0,0,0,.1)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">

            <!-- Nombre + badge -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;margin-bottom:.85rem;">
                <div>
                    <p style="font-weight:900;font-size:.95rem;color:#1c1917;line-height:1.3;"><?= htmlspecialchars($prod['nombre']) ?></p>
                    <?php if ($prod['nombre_categoria']): ?>
                    <p style="font-size:.72rem;color:#a8a29e;margin-top:.15rem;"><?= htmlspecialchars($prod['nombre_categoria']) ?></p>
                    <?php endif; ?>
                </div>
                <span style="display:inline-flex;align-items:center;gap:.25rem;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;font-size:.68rem;font-weight:700;padding:.25rem .6rem;border-radius:999px;white-space:nowrap;flex-shrink:0;">
                    <i class="fa-solid <?= $badgeIcon ?>" style="font-size:.55rem;"></i>
                    <?= $badgeLabel ?>
                </span>
            </div>

            <!-- Stock grande -->
            <div style="display:flex;align-items:baseline;gap:.35rem;margin-bottom:.7rem;">
                <span style="font-size:2.4rem;font-weight:900;color:<?= $barColor ?>;line-height:1;"><?= $stock ?></span>
                <span style="font-size:.82rem;color:#78716c;font-weight:600;">unidades</span>
            </div>

            <!-- Barra de nivel -->
            <div style="background:rgba(0,0,0,.08);border-radius:999px;height:8px;overflow:hidden;margin-bottom:.6rem;">
                <div style="width:<?= $pct ?>%;height:100%;border-radius:999px;background:<?= $barColor ?>;transition:width .5s;"></div>
            </div>

            <!-- Info mínimo + precio -->
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <p style="font-size:.73rem;color:#78716c;">
                    <i class="fa-solid fa-arrow-trend-down" style="color:<?= $barColor ?>;margin-right:.2rem;"></i>
                    Mín: <strong><?= $minimo ?></strong>
                </p>
                <p style="font-size:.8rem;font-weight:900;color:#ea580c;">
                    $<?= number_format((float)$prod['precio'], 0, ',', '.') ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sin resultados -->
    <div id="stk-sin-res" style="display:none;text-align:center;padding:3rem;color:#a8a29e;">
        <i class="fa-solid fa-magnifying-glass" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#d6d3d1;"></i>
        <p style="font-size:1rem;font-weight:700;">No se encontraron productos</p>
    </div>

</div><!-- /space-y-6 -->

<script>
var stkFiltro = 'todos';

function filtrarStock(estado) {
    stkFiltro = estado;
    // Resetear todos los botones
    ['todos','disponible','bajo','agotado'].forEach(function(e) {
        var btn = document.getElementById('stk-' + e);
        if (!btn) return;
        btn.style.background   = '#fff';
        btn.style.color        = '#78716c';
        btn.style.borderColor  = '#e7e5e4';
    });
    // Activar el seleccionado
    var activo = document.getElementById('stk-' + estado);
    if (activo) {
        activo.style.background  = 'linear-gradient(135deg,#ea580c,#f59e0b)';
        activo.style.color       = '#fff';
        activo.style.borderColor = 'transparent';
    }
    buscarStock();
}

function buscarStock() {
    var q   = (document.getElementById('stk-buscar').value || '').toLowerCase();
    var vis = 0;
    document.querySelectorAll('.stk-card').forEach(function(c) {
        var estadoOk = stkFiltro === 'todos' || c.dataset.estado === stkFiltro;
        var nombreOk = c.dataset.nombre.includes(q);
        c.style.display = (estadoOk && nombreOk) ? '' : 'none';
        if (estadoOk && nombreOk) vis++;
    });
    document.getElementById('stk-sin-res').style.display = vis === 0 ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
