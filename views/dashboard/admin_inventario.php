<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Inventario.php';
require_once __DIR__ . '/../../models/Producto.php';

$db      = (new Database())->conectar();
$invModel = new Inventario($db);
$prodModel = new Producto($db);

$mensaje = '';
$error   = '';

/* ── REGISTRAR PRODUCTO SIN INVENTARIO ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'registrar_producto') {
    $idProd      = (int)($_POST['id_producto']    ?? 0);
    $stockInicial = (int)($_POST['stock_inicial'] ?? 0);
    $stockMinimo  = (int)($_POST['stock_minimo']  ?? 5);

    if ($idProd <= 0) {
        $error = 'Selecciona un producto válido.';
    } else {
        $sqlChk = "SELECT COUNT(*) FROM inventario WHERE id_producto = :id";
        $stmtChk = $db->prepare($sqlChk);
        $stmtChk->execute([':id' => $idProd]);
        if ((int)$stmtChk->fetchColumn() > 0) {
            $error = 'Este producto ya tiene registro en inventario.';
        } else {
            $sqlIns = "INSERT INTO inventario (id_producto, cantidad_actual, cantidad_minima, fecha_actualizacion)
                       VALUES (:id, :stock, :minimo, CURDATE())";
            $stmtIns = $db->prepare($sqlIns);
            $ok = $stmtIns->execute([':id' => $idProd, ':stock' => $stockInicial, ':minimo' => $stockMinimo]);
            if ($ok && $stockInicial > 0) {
                $invModel->registrarMovimiento($idProd, 'entrada', $stockInicial, 'Stock inicial registrado');
            }
            $mensaje = $ok ? 'Producto registrado en inventario correctamente.' : 'Error al registrar.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'suministro') {
    $idInv      = (int)($_POST['id_inventario'] ?? 0);
    $cantidad   = (int)($_POST['cantidad']       ?? 0);
    $descripcion = trim($_POST['descripcion']    ?? '');

    if ($idInv <= 0 || $cantidad <= 0) {
        $error = 'Datos inválidos. La cantidad debe ser mayor a 0.';
    } elseif ($invModel->agregarSuministro($idInv, $cantidad, $descripcion)) {
        $mensaje = "Suministro agregado correctamente. Stock actualizado.";
    } else {
        $error = 'Error al registrar el suministro.';
    }
}

/* ── ACTUALIZAR STOCK MÍNIMO ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'minimo') {
    $idInv  = (int)($_POST['id_inventario'] ?? 0);
    $minimo = (int)($_POST['cantidad_minima'] ?? 0);

    if ($idInv > 0 && $minimo >= 0 && $invModel->actualizarMinimo($idInv, $minimo)) {
        $mensaje = 'Stock mínimo actualizado.';
    } else {
        $error = 'Error al actualizar el stock mínimo.';
    }
}

$stats     = $invModel->stats();
$items     = $invModel->obtenerTodos();
$historial = $invModel->historial(30);
$titulo    = 'Inventario';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<style>
.inv-card { background:rgba(255,247,237,.95); border:1px solid rgba(251,146,60,.18); border-radius:22px; padding:1.5rem; }
.kpi      { border-radius:20px; padding:1.4rem; display:flex; align-items:center; gap:1rem; transition:transform .2s; }
.kpi:hover{ transform:translateY(-3px); }
.kpi-ico  { width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
.badge-disponible { background:#dcfce7; color:#15803d; }
.badge-bajo       { background:#fef9c3; color:#a16207; }
.badge-agotado    { background:#fee2e2; color:#b91c1c; }
.inv-table        { width:100%; border-collapse:collapse; font-size:.9rem; }
.inv-table thead tr { background:linear-gradient(135deg,#ea580c,#f59e0b); color:#fff; }
.inv-table thead th { padding:.8rem 1rem; text-align:left; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.inv-table tbody tr { border-bottom:1px solid #f5f0eb; transition:background .15s; }
.inv-table tbody tr:hover { background:#fff7ed; }
.inv-table tbody td { padding:.75rem 1rem; }
.progress-wrap { background:#f5f0eb; border-radius:999px; height:8px; overflow:hidden; min-width:80px; }
.progress-fill { height:100%; border-radius:999px; transition:width .4s; }
</style>

<div class="space-y-6">

    <!-- ALERTAS -->
    <?php if ($mensaje !== ''): ?>
    <div style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;font-weight:700;">
        <i class="fa-solid fa-circle-check text-xl"></i> <?= htmlspecialchars($mensaje) ?>
    </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;font-weight:700;">
        <i class="fa-solid fa-circle-exclamation text-xl"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="kpi inv-card">
            <div class="kpi-ico" style="background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Total Productos</p>
                <p style="font-size:2rem;font-weight:900;color:#1c1917;"><?= $stats['total'] ?></p>
            </div>
        </div>
        <div class="kpi inv-card">
            <div class="kpi-ico" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Agotados</p>
                <p style="font-size:2rem;font-weight:900;color:#dc2626;"><?= $stats['agotados'] ?></p>
            </div>
        </div>
        <div class="kpi inv-card">
            <div class="kpi-ico" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div>
                <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Stock Bajo</p>
                <p style="font-size:2rem;font-weight:900;color:#d97706;"><?= $stats['stock_bajo'] ?></p>
            </div>
        </div>
        <div class="kpi inv-card">
            <div class="kpi-ico" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div>
                <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;">Entradas Hoy</p>
                <p style="font-size:2rem;font-weight:900;color:#16a34a;"><?= $stats['entradas_hoy'] ?></p>
            </div>
        </div>
    </div>

    <!-- TABLA INVENTARIO -->
    <div class="inv-card">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
                <div>
                    <h2 style="font-size:1.3rem;font-weight:900;color:#1c1917;">Control de Inventario</h2>
                    <p style="font-size:.8rem;color:#78716c;"><?= count($items) ?> producto(s) registrado(s)</p>
                </div>
            </div>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
                <button onclick="abrir('modalRegistrarProducto')"
                    style="display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;font-size:.85rem;padding:.6rem 1.2rem;border-radius:12px;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(234,88,12,.3);">
                    <i class="fa-solid fa-plus"></i> Agregar Producto
                </button>
                <input type="text" id="buscador" placeholder="Buscar producto..." oninput="filtrar()"
                    style="padding:.6rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.85rem;outline:none;width:200px;"
                    onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                <select id="filtroEstado" onchange="filtrar()"
                    style="padding:.6rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.85rem;outline:none;"
                    onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                    <option value="">Todos los estados</option>
                    <option value="disponible">Disponible</option>
                    <option value="bajo">Stock bajo</option>
                    <option value="agotado">Agotado</option>
                </select>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="inv-table" id="tablaInv">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                        <th>Actualizado</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:3rem;color:#a8a29e;">
                        <i class="fa-solid fa-boxes-stacked" style="font-size:2.5rem;display:block;margin-bottom:.5rem;color:#d6d3d1;"></i>
                        No hay productos en inventario
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $item):
                        $pct = $item['cantidad_minima'] > 0
                            ? min(100, round(($item['cantidad_actual'] / ($item['cantidad_minima'] * 2)) * 100))
                            : ($item['cantidad_actual'] > 0 ? 100 : 0);
                        $barColor = match($item['estado']) {
                            'agotado' => '#ef4444',
                            'bajo'    => '#f59e0b',
                            default   => '#22c55e',
                        };
                        $badgeClass = 'badge-' . $item['estado'];
                        $badgeLabel = match($item['estado']) {
                            'agotado' => 'Sin stock',
                            'bajo'    => 'Stock bajo',
                            default   => 'Disponible',
                        };
                    ?>
                    <tr class="fila-inv" data-estado="<?= $item['estado'] ?>" data-nombre="<?= strtolower(htmlspecialchars($item['producto'])) ?>">
                        <td style="color:#a8a29e;font-family:monospace;"><?= $i+1 ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.6rem;">
                                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;display:flex;align-items:center;justify-content:center;color:#ea580c;font-size:.9rem;">
                                    <i class="fa-solid fa-utensils"></i>
                                </div>
                                <span style="font-weight:700;color:#1c1917;"><?= htmlspecialchars($item['producto']) ?></span>
                            </div>
                        </td>
                        <td style="font-weight:700;color:#ea580c;">$<?= number_format((float)$item['precio'],0) ?></td>
                        <td>
                            <span style="font-size:1.2rem;font-weight:900;color:<?= $barColor ?>;">
                                <?= (int)$item['cantidad_actual'] ?>
                            </span>
                            <span style="font-size:.75rem;color:#a8a29e;"> uds.</span>
                        </td>
                        <td style="color:#78716c;"><?= (int)$item['cantidad_minima'] ?> uds.</td>
                        <td style="min-width:90px;">
                            <div class="progress-wrap">
                                <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
                            </div>
                            <span style="font-size:.7rem;color:#a8a29e;"><?= $pct ?>%</span>
                        </td>
                        <td>
                            <span class="<?= $badgeClass ?>" style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;padding:.25rem .75rem;border-radius:999px;">
                                <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                                <?= $badgeLabel ?>
                            </span>
                        </td>
                        <td style="font-size:.8rem;color:#a8a29e;">
                            <?= $item['fecha_actualizacion'] ? date('d/m/Y', strtotime($item['fecha_actualizacion'])) : '—' ?>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex;gap:.4rem;justify-content:center;flex-wrap:wrap;">
                                <button onclick="abrirSuministro(<?= (int)$item['id_inventario'] ?>, '<?= htmlspecialchars(addslashes($item['producto'])) ?>', <?= (int)$item['cantidad_actual'] ?>)"
                                    style="display:inline-flex;align-items:center;gap:.3rem;background:#dcfce7;color:#15803d;font-weight:700;font-size:.75rem;padding:.4rem .8rem;border-radius:8px;border:none;cursor:pointer;transition:background .2s;"
                                    onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                                    <i class="fa-solid fa-plus"></i> Suministro
                                </button>
                                <button onclick="abrirMinimo(<?= (int)$item['id_inventario'] ?>, '<?= htmlspecialchars(addslashes($item['producto'])) ?>', <?= (int)$item['cantidad_minima'] ?>)"
                                    style="display:inline-flex;align-items:center;gap:.3rem;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:.75rem;padding:.4rem .8rem;border-radius:8px;border:none;cursor:pointer;transition:background .2s;"
                                    onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                                    <i class="fa-solid fa-sliders"></i> Mínimo
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- HISTORIAL DE MOVIMIENTOS -->
    <div class="inv-card">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
            <div style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <h2 style="font-size:1.3rem;font-weight:900;color:#1c1917;">Historial de Movimientos</h2>
                <p style="font-size:.8rem;color:#78716c;">Últimos 30 movimientos registrados</p>
            </div>
        </div>

        <?php if (empty($historial)): ?>
            <p style="text-align:center;padding:2rem;color:#a8a29e;">No hay movimientos registrados aún.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th style="text-align:center;">Tipo</th>
                        <th style="text-align:center;">Cantidad</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $h):
                    $esEntrada = $h['tipo_movimiento'] === 'entrada';
                ?>
                <tr>
                    <td style="color:#a8a29e;font-family:monospace;"><?= (int)$h['id_movimiento'] ?></td>
                    <td style="font-size:.82rem;color:#78716c;">
                        <?= $h['fecha_movimiento'] ? date('d/m/Y', strtotime($h['fecha_movimiento'])) : '—' ?>
                    </td>
                    <td style="font-weight:600;color:#1c1917;"><?= htmlspecialchars($h['producto']) ?></td>
                    <td style="text-align:center;">
                        <?php if ($esEntrada): ?>
                            <span style="display:inline-flex;align-items:center;gap:.3rem;background:#dcfce7;color:#15803d;font-size:.75rem;font-weight:700;padding:.25rem .7rem;border-radius:999px;">
                                <i class="fa-solid fa-arrow-up" style="font-size:.6rem;"></i> Entrada
                            </span>
                        <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:.3rem;background:#fee2e2;color:#b91c1c;font-size:.75rem;font-weight:700;padding:.25rem .7rem;border-radius:999px;">
                                <i class="fa-solid fa-arrow-down" style="font-size:.6rem;"></i> Salida
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;font-weight:900;font-size:1rem;color:<?= $esEntrada ? '#16a34a' : '#dc2626' ?>;">
                        <?= $esEntrada ? '+' : '-' ?><?= (int)$h['cantidad'] ?>
                    </td>
                    <td style="font-size:.82rem;color:#78716c;"><?= htmlspecialchars($h['descripcion'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /space-y-6 -->


<!-- ══ MODAL: REGISTRAR PRODUCTO EN INVENTARIO ══ -->
<div id="modalRegistrarProducto" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:24px;padding:2rem;width:100%;max-width:440px;position:relative;box-shadow:0 30px 80px rgba(0,0,0,.3);animation:popIn .22s ease;">
        <button onclick="cerrar('modalRegistrarProducto')" style="position:absolute;top:1rem;right:1rem;background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;">Agregar Producto al Inventario</h3>
                <p style="font-size:.8rem;color:#78716c;">Registra un producto con su stock inicial</p>
            </div>
        </div>

        <?php
        // Productos que aún NO tienen inventario
        $sqlSinInv = "SELECT p.id_producto, p.nombre FROM producto p
                      WHERE p.id_producto NOT IN (SELECT id_producto FROM inventario WHERE id_producto IS NOT NULL)
                      ORDER BY p.nombre ASC";
        $sinInv = $db->query($sqlSinInv)->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (empty($sinInv)): ?>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1rem;text-align:center;color:#15803d;font-weight:700;font-size:.9rem;">
                <i class="fa-solid fa-circle-check" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                ¡Todos los productos ya están en inventario!
            </div>
            <div style="margin-top:1.5rem;">
                <button type="button" onclick="cerrar('modalRegistrarProducto')"
                    style="width:100%;padding:.75rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">
                    Cerrar
                </button>
            </div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="accion" value="registrar_producto">

            <label style="display:block;font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.4rem;">Producto *</label>
            <select name="id_producto" required
                style="width:100%;padding:.75rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.9rem;outline:none;margin-bottom:1rem;background:#fff;"
                onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                <option value="">Selecciona un producto...</option>
                <?php foreach ($sinInv as $sp): ?>
                    <option value="<?= (int)$sp['id_producto'] ?>"><?= htmlspecialchars($sp['nombre']) ?></option>
                <?php endforeach; ?>
            </select>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;">
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.4rem;">
                        <i class="fa-solid fa-boxes-stacked" style="color:#ea580c;"></i> Stock inicial
                    </label>
                    <input type="number" name="stock_inicial" min="0" value="0" required
                        style="width:100%;padding:.75rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.9rem;outline:none;"
                        onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor='#e7e5e4'">
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.4rem;">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;"></i> Stock mínimo
                    </label>
                    <input type="number" name="stock_minimo" min="0" value="5" required
                        style="width:100%;padding:.75rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.9rem;outline:none;"
                        onfocus="this.style.borderColor='#f59e0b'" onblur="this.style.borderColor='#e7e5e4'">
                </div>
            </div>

            <p style="font-size:.75rem;color:#a8a29e;margin-bottom:1.25rem;">
                <i class="fa-solid fa-circle-info" style="color:#ea580c;"></i>
                El stock mínimo genera alertas cuando el inventario baje de ese nivel.
            </p>

            <div style="display:flex;gap:.75rem;">
                <button type="button" onclick="cerrar('modalRegistrarProducto')"
                    style="flex:1;padding:.75rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;font-size:.9rem;">
                    Cancelar
                </button>
                <button type="submit"
                    style="flex:1;padding:.75rem;border-radius:12px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;cursor:pointer;font-size:.9rem;box-shadow:0 4px 14px rgba(234,88,12,.3);">
                    <i class="fa-solid fa-plus"></i> Registrar
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- ══ MODAL: AGREGAR SUMINISTRO ══ -->
<div id="modalSuministro" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:24px;padding:2rem;width:100%;max-width:420px;position:relative;box-shadow:0 30px 80px rgba(0,0,0,.3);animation:popIn .22s ease;">
        <button onclick="cerrar('modalSuministro')" style="position:absolute;top:1rem;right:1rem;background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                <i class="fa-solid fa-plus"></i>
            </div>
            <div>
                <h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;">Agregar Suministro</h3>
                <p id="suministro_producto" style="font-size:.8rem;color:#78716c;"></p>
            </div>
        </div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
            <i class="fa-solid fa-circle-info"></i>
            Stock actual: <strong id="suministro_stock" style="font-size:1rem;margin-left:.25rem;"></strong> unidades
        </div>
        <form method="POST">
            <input type="hidden" name="accion"        value="suministro">
            <input type="hidden" name="id_inventario" id="suministro_id">
            <label style="display:block;font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.4rem;">Cantidad a agregar *</label>
            <input type="number" name="cantidad" min="1" required placeholder="Ej. 20"
                style="width:100%;padding:.75rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.95rem;outline:none;margin-bottom:1rem;"
                onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor='#e7e5e4'">
            <label style="display:block;font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.4rem;">Descripción</label>
            <input type="text" name="descripcion" placeholder="Ej. Compra proveedor semanal"
                style="width:100%;padding:.75rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.95rem;outline:none;margin-bottom:1.5rem;"
                onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor='#e7e5e4'">
            <div style="display:flex;gap:.75rem;">
                <button type="button" onclick="cerrar('modalSuministro')"
                    style="flex:1;padding:.75rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;font-size:.9rem;">
                    Cancelar
                </button>
                <button type="submit"
                    style="flex:1;padding:.75rem;border-radius:12px;border:none;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-weight:900;cursor:pointer;font-size:.9rem;box-shadow:0 4px 14px rgba(34,197,94,.35);">
                    <i class="fa-solid fa-plus mr-1"></i> Agregar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: EDITAR STOCK MÍNIMO ══ -->
<div id="modalMinimo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:24px;padding:2rem;width:100%;max-width:380px;position:relative;box-shadow:0 30px 80px rgba(0,0,0,.3);animation:popIn .22s ease;">
        <button onclick="cerrar('modalMinimo')" style="position:absolute;top:1rem;right:1rem;background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0ea5e9,#0369a1);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div>
                <h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;">Stock Mínimo</h3>
                <p id="minimo_producto" style="font-size:.8rem;color:#78716c;"></p>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="accion"        value="minimo">
            <input type="hidden" name="id_inventario" id="minimo_id">
            <label style="display:block;font-size:.75rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.4rem;">Nuevo stock mínimo *</label>
            <input type="number" name="cantidad_minima" id="minimo_valor" min="0" required
                style="width:100%;padding:.75rem 1rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.95rem;outline:none;margin-bottom:1.5rem;"
                onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e7e5e4'">
            <div style="display:flex;gap:.75rem;">
                <button type="button" onclick="cerrar('modalMinimo')"
                    style="flex:1;padding:.75rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;font-size:.9rem;">
                    Cancelar
                </button>
                <button type="submit"
                    style="flex:1;padding:.75rem;border-radius:12px;border:none;background:linear-gradient(135deg,#0ea5e9,#0369a1);color:#fff;font-weight:900;cursor:pointer;font-size:.9rem;box-shadow:0 4px 14px rgba(14,165,233,.35);">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes popIn { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }
</style>

<script>
/* ── Modales ── */
function abrir(id) {
    var m = document.getElementById(id);
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function cerrar(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}
['modalSuministro','modalMinimo','modalRegistrarProducto'].forEach(function(id){
    document.getElementById(id).addEventListener('click', function(e){ if(e.target===this) cerrar(id); });
});
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ cerrar('modalSuministro'); cerrar('modalMinimo'); cerrar('modalRegistrarProducto'); } });

function abrirSuministro(id, nombre, stock) {
    document.getElementById('suministro_id').value      = id;
    document.getElementById('suministro_producto').textContent = nombre;
    document.getElementById('suministro_stock').textContent    = stock;
    abrir('modalSuministro');
}
function abrirMinimo(id, nombre, minimo) {
    document.getElementById('minimo_id').value          = id;
    document.getElementById('minimo_producto').textContent    = nombre;
    document.getElementById('minimo_valor').value       = minimo;
    abrir('modalMinimo');
}

/* ── Filtro tabla ── */
function filtrar() {
    var q      = document.getElementById('buscador').value.toLowerCase();
    var estado = document.getElementById('filtroEstado').value;
    document.querySelectorAll('.fila-inv').forEach(function(fila) {
        var nombreOk  = fila.dataset.nombre.includes(q);
        var estadoOk  = !estado || fila.dataset.estado === estado;
        fila.style.display = (nombreOk && estadoOk) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
