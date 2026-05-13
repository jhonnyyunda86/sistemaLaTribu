<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'mesero') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Inventario.php';

$db          = (new Database())->conectar();
$pedidoModel = new Pedido($db);
$prodModel   = new Producto($db);
$invModel    = new Inventario($db);

$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
$idMesero  = $pedidoModel->obtenerIdMesero($idUsuario);
$mensaje   = '';
$error     = '';

/* ── CAMBIAR ESTADO ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
    $idPedido = (int)($_POST['id_pedido']     ?? 0);
    $idEstado = (int)($_POST['id_estado_pedido'] ?? 0);
    if ($idPedido > 0 && $idEstado > 0 && $pedidoModel->cambiarEstado($idPedido, $idEstado)) {
        $mensaje = 'Estado del pedido actualizado.';
    } else {
        $error = 'Error al actualizar el estado.';
    }
}

/* ── CREAR PEDIDO DESDE MESERO ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_pedido') {
    $idTipo    = (int)($_POST['id_tipo_pedido'] ?? 1);
    $itemsJson = trim($_POST['items'] ?? '');
    $items     = json_decode($itemsJson, true);

    if (!$idMesero) {
        $error = 'Tu usuario no tiene un registro de mesero. Contacta al administrador.';
    } elseif (empty($items) || !is_array($items)) {
        $error = 'El pedido está vacío.';
    } else {
        $resultado = $pedidoModel->crearPedidoMesero($idMesero, $idTipo, $items, $db);
        if ($resultado['ok']) {
            $mensaje = "Pedido #{$resultado['id_pedido']} creado correctamente. Total: $" . number_format($resultado['total'], 0, ',', '.');
        } else {
            $error = 'Error al crear el pedido: ' . ($resultado['msg'] ?? '');
        }
    }
}

$pedidos    = $pedidoModel->obtenerTodosMesero();
$productos  = $prodModel->obtenerMenuCliente();
$stockItems = $invModel->obtenerTodos();   // para la sección de stock
$titulo     = 'Gestión de Pedidos';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<style>
.mes-card { background:rgba(255,247,237,.95);border:1px solid rgba(251,146,60,.18);border-radius:20px;padding:1.5rem; }
.estado-btn { display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;font-weight:700;padding:.3rem .8rem;border-radius:999px;border:none;cursor:pointer;transition:all .2s; }
.badge-Pendiente      { background:#fef9c3;color:#a16207; }
.badge-En-preparación { background:#dbeafe;color:#1d4ed8; }
.badge-Entregado      { background:#dcfce7;color:#15803d; }
.badge-Cancelado      { background:#fee2e2;color:#b91c1c; }
.ped-table { width:100%;border-collapse:collapse;font-size:.88rem; }
.ped-table thead tr { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff; }
.ped-table thead th { padding:.75rem 1rem;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
.ped-table tbody tr { border-bottom:1px solid #f5f0eb;transition:background .15s; }
.ped-table tbody tr:hover { background:#fff7ed; }
.ped-table tbody td { padding:.75rem 1rem; }
/* Nuevo pedido */
.prod-sel-card { background:#fff;border:2px solid #f5f0eb;border-radius:14px;padding:.75rem;display:flex;align-items:center;gap:.75rem;cursor:pointer;transition:all .2s; }
.prod-sel-card:hover { border-color:#fdba74;background:#fff7ed; }
.prod-sel-card.en-pedido { border-color:#ea580c;background:linear-gradient(135deg,#fff7ed,#ffedd5); }
/* Modal */
#modalDetalle,#modalNuevoPedido { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center; }
.det-box,.np-box { background:#fff;border-radius:24px;width:100%;max-width:560px;max-height:88vh;overflow-y:auto;padding:1.75rem;box-shadow:0 30px 80px rgba(0,0,0,.3);animation:popIn .22s ease; }
.np-box { max-width:680px; }
@keyframes popIn { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }
</style>

<div class="space-y-6">

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
            <h1 class="text-3xl font-black text-white">Gestión de Pedidos</h1>
            <p class="text-orange-200 text-sm mt-1"><?= count($pedidos) ?> pedido(s) en el sistema</p>
        </div>
        <button onclick="abrirNuevoPedido()"
            style="display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;padding:.75rem 1.5rem;border-radius:16px;border:none;cursor:pointer;box-shadow:0 4px 16px rgba(234,88,12,.35);font-size:.95rem;">
            <i class="fa-solid fa-plus"></i> Nuevo Pedido
        </button>
    </div>
</div>

<!-- KPIs rápidos -->
<?php
$kCounts = ['Pendiente'=>0,'En preparación'=>0,'Entregado'=>0,'Cancelado'=>0];
foreach ($pedidos as $p) { $e = $p['estado']??''; if(isset($kCounts[$e])) $kCounts[$e]++; }
$kpis = [
    ['Pendientes',     $kCounts['Pendiente'],      'fa-clock',            '#fef9c3','#a16207'],
    ['En preparación', $kCounts['En preparación'], 'fa-fire-flame-curved','#dbeafe','#1d4ed8'],
    ['Entregados',     $kCounts['Entregado'],      'fa-circle-check',     '#dcfce7','#15803d'],
    ['Cancelados',     $kCounts['Cancelado'],      'fa-ban',              '#fee2e2','#b91c1c'],
];
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($kpis as $k): ?>
    <div class="mes-card flex items-center gap-3 hover:-translate-y-1 transition">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $k[3] ?>;color:<?= $k[4] ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
            <i class="fa-solid <?= $k[2] ?>"></i>
        </div>
        <div>
            <p style="font-size:.7rem;font-weight:700;color:#78716c;text-transform:uppercase;"><?= $k[0] ?></p>
            <p style="font-size:1.8rem;font-weight:900;color:#1c1917;"><?= $k[1] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- TABLA PEDIDOS -->
<div class="mes-card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h2 style="font-size:1.2rem;font-weight:900;color:#1c1917;">Todos los Pedidos</h2>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <input type="text" id="filtro-ped" placeholder="Buscar..." oninput="filtrarPedidos()"
                style="padding:.5rem .9rem;border:2px solid #e7e5e4;border-radius:11px;font-size:.83rem;outline:none;width:160px;"
                onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
            <select id="filtro-estado-ped" onchange="filtrarPedidos()"
                style="padding:.5rem .9rem;border:2px solid #e7e5e4;border-radius:11px;font-size:.83rem;outline:none;background:#fff;cursor:pointer;"
                onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En preparación">En preparación</option>
                <option value="Entregado">Entregado</option>
                <option value="Cancelado">Cancelado</option>
            </select>
        </div>
    </div>

    <?php if (empty($pedidos)): ?>
    <div style="text-align:center;padding:3rem;color:#a8a29e;">
        <i class="fa-solid fa-receipt" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#d6d3d1;"></i>
        <p style="font-weight:700;">No hay pedidos registrados</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="ped-table">
            <thead><tr>
                <th>#</th><th>Fecha</th><th>Cliente</th><th>Tipo</th>
                <th>Productos</th><th>Total</th><th>Estado</th><th style="text-align:center;">Acciones</th>
            </tr></thead>
            <tbody id="cuerpo-pedidos">
            <?php foreach ($pedidos as $p):
                $estado     = $p['estado'] ?? 'Pendiente';
                $estadoSlug = str_replace(' ','-',$estado);
                $tipoIcon   = match($p['tipo']??'') {'Domicilio'=>'fa-motorcycle','Para llevar'=>'fa-bag-shopping',default=>'fa-chair'};
            ?>
            <tr class="fila-ped" data-estado="<?= htmlspecialchars($estado) ?>"
                data-texto="<?= strtolower('#'.$p['id_pedido'].' '.($p['cliente']??'').' '.($p['tipo']??'').' '.$estado) ?>">
                <td><span style="font-family:monospace;font-weight:900;color:#ea580c;">#<?= (int)$p['id_pedido'] ?></span></td>
                <td style="font-size:.8rem;color:#78716c;">
                    <?= isset($p['fecha_pedido']) ? date('d/m/Y', strtotime($p['fecha_pedido'])) : '—' ?><br>
                    <span style="font-size:.72rem;color:#a8a29e;"><?= isset($p['fecha_pedido']) ? date('H:i', strtotime($p['fecha_pedido'])) : '' ?></span>
                </td>
                <td style="font-weight:600;color:#1c1917;"><?= htmlspecialchars($p['cliente'] ?? '—') ?></td>
                <td><span style="font-size:.8rem;font-weight:700;color:#78716c;display:inline-flex;align-items:center;gap:.3rem;"><i class="fa-solid <?= $tipoIcon ?> text-orange-500"></i><?= htmlspecialchars($p['tipo']??'—') ?></span></td>
                <td style="text-align:center;"><span style="background:#fff7ed;color:#ea580c;font-weight:900;font-size:.82rem;padding:.2rem .6rem;border-radius:999px;"><?= (int)$p['num_productos'] ?></span></td>
                <td style="font-weight:900;color:#ea580c;">$<?= number_format((float)$p['total'],0,',','.') ?></td>
                <td>
                    <span class="badge-<?= $estadoSlug ?>" style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;padding:.3rem .75rem;border-radius:999px;">
                        <?= htmlspecialchars($estado) ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap;">
                        <button onclick="verDetalleMesero(<?= (int)$p['id_pedido'] ?>)"
                            style="display:inline-flex;align-items:center;gap:.3rem;background:#fff7ed;border:1px solid #fed7aa;color:#ea580c;font-weight:700;font-size:.72rem;padding:.35rem .7rem;border-radius:8px;cursor:pointer;"
                            onmouseover="this.style.background='#ffedd5'" onmouseout="this.style.background='#fff7ed'">
                            <i class="fa-solid fa-eye"></i> Ver
                        </button>
                        <?php if ($estado !== 'Entregado' && $estado !== 'Cancelado'): ?>
                        <button onclick="abrirCambioEstado(<?= (int)$p['id_pedido'] ?>, '<?= htmlspecialchars($estado) ?>')"
                            style="display:inline-flex;align-items:center;gap:.3rem;background:#dbeafe;border:1px solid #bfdbfe;color:#1d4ed8;font-weight:700;font-size:.72rem;padding:.35rem .7rem;border-radius:8px;cursor:pointer;"
                            onmouseover="this.style.background='#bfdbfe'" onmouseout="this.style.background='#dbeafe'">
                            <i class="fa-solid fa-arrows-rotate"></i> Estado
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="sin-ped" style="display:none;text-align:center;padding:2rem;color:#a8a29e;">
        <p style="font-weight:700;">No se encontraron pedidos</p>
    </div>
    <?php endif; ?>
</div><!-- /mes-card tabla pedidos -->
</div><!-- /space-y-6 -->

<!-- ══ MODAL DETALLE ══ -->
<div id="modalDetalle">
<div class="det-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-receipt"></i></div>
            <div><h3 id="det-titulo" style="font-size:1.1rem;font-weight:900;color:#1c1917;">Detalle</h3><p id="det-fecha" style="font-size:.78rem;color:#78716c;"></p></div>
        </div>
        <button onclick="cerrarModal('modalDetalle')" style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="det-info" style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1rem;"></div>
    <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.6rem;padding-bottom:.4rem;border-bottom:1px solid #f5f0eb;"><i class="fa-solid fa-utensils text-orange-500 mr-1"></i> Productos</p>
    <div id="det-productos" style="margin-bottom:1rem;"></div>
    <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;border-radius:12px;padding:.85rem 1rem;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-weight:700;color:#78716c;">Total</span>
        <span id="det-total" style="font-size:1.3rem;font-weight:900;color:#ea580c;"></span>
    </div>
    <button onclick="cerrarModal('modalDetalle')" style="width:100%;margin-top:.75rem;padding:.7rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">Cerrar</button>
</div>
</div>

<!-- ══ MODAL CAMBIO ESTADO ══ -->
<div id="modalEstado" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:20px;width:100%;max-width:380px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:popIn .22s ease;">
    <h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;margin-bottom:1.25rem;"><i class="fa-solid fa-arrows-rotate text-orange-500 mr-2"></i>Cambiar Estado</h3>
    <form method="POST">
        <input type="hidden" name="accion"    value="cambiar_estado">
        <input type="hidden" name="id_pedido" id="est-id">
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.25rem;">
            <?php
            $estados = [1=>'Pendiente',2=>'En preparación',3=>'Entregado',4=>'Cancelado'];
            $colores = [1=>'#fef9c3|#a16207',2=>'#dbeafe|#1d4ed8',3=>'#dcfce7|#15803d',4=>'#fee2e2|#b91c1c'];
            foreach ($estados as $id => $nombre):
                [$bg,$color] = explode('|',$colores[$id]);
            ?>
            <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;transition:border-color .2s;"
                   onmouseover="this.style.borderColor='<?= $color ?>'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_pedido" value="<?= $id ?>" style="accent-color:<?= $color ?>;">
                <span style="background:<?= $bg ?>;color:<?= $color ?>;font-weight:700;font-size:.82rem;padding:.25rem .75rem;border-radius:999px;"><?= $nombre ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:.6rem;">
            <button type="button" onclick="cerrarModal('modalEstado')" style="flex:1;padding:.7rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">Cancelar</button>
            <button type="submit" style="flex:1;padding:.7rem;border-radius:12px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;cursor:pointer;">Guardar</button>
        </div>
    </form>
</div>
</div>

<!-- ══ MODAL NUEVO PEDIDO ══ -->
<div id="modalNuevoPedido">
<div class="np-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fa-solid fa-plus"></i></div>
            <div><h3 style="font-size:1.15rem;font-weight:900;color:#1c1917;">Nuevo Pedido</h3><p style="font-size:.78rem;color:#78716c;">Selecciona productos y tipo de pedido</p></div>
        </div>
        <button onclick="cerrarModal('modalNuevoPedido')" style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Tipo de pedido -->
    <div style="margin-bottom:1rem;">
        <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.5rem;">Tipo de pedido</p>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button class="tipo-btn activo-tipo" onclick="selTipo(this,1)" data-tipo="1"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-chair"></i> Mesa
            </button>
            <button class="tipo-btn" onclick="selTipo(this,2)" data-tipo="2"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-motorcycle"></i> Domicilio
            </button>
            <button class="tipo-btn" onclick="selTipo(this,3)" data-tipo="3"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-bag-shopping"></i> Para llevar
            </button>
        </div>
        <input type="hidden" id="np-tipo" value="1">
    </div>

    <!-- Buscador productos -->
    <div style="position:relative;margin-bottom:.75rem;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#a8a29e;pointer-events:none;font-size:.85rem;"></i>
        <input type="text" id="np-buscar" placeholder="Buscar producto..." oninput="filtrarProdsNP()"
            style="width:100%;padding:.65rem .9rem .65rem 2.4rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.88rem;outline:none;"
            onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
    </div>

    <!-- Grid productos -->
    <div id="np-productos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.6rem;max-height:280px;overflow-y:auto;margin-bottom:1rem;padding-right:.25rem;">
        <?php foreach ($productos as $prod):
            $agotado = ($prod['estado_stock'] ?? '') === 'agotado';
        ?>
        <div class="prod-sel-card <?= $agotado?'opacity-40 cursor-not-allowed':'' ?>"
             data-id="<?= (int)$prod['id_producto'] ?>"
             data-nombre="<?= htmlspecialchars(addslashes($prod['nombre'])) ?>"
             data-precio="<?= (float)$prod['precio'] ?>"
             data-stock="<?= (int)$prod['stock'] ?>"
             data-nombre-lower="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>"
             <?= $agotado ? '' : 'onclick="toggleProducto(this)"' ?>>
            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;display:flex;align-items:center;justify-content:center;color:#ea580c;font-size:.85rem;flex-shrink:0;">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-weight:700;font-size:.82rem;color:#1c1917;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($prod['nombre']) ?></p>
                <p style="font-size:.75rem;color:#ea580c;font-weight:700;">$<?= number_format((float)$prod['precio'],0,',','.') ?></p>
                <?php if ($agotado): ?>
                <p style="font-size:.68rem;color:#b91c1c;font-weight:700;">Sin stock</p>
                <?php else: ?>
                <p style="font-size:.68rem;color:#78716c;"><?= (int)$prod['stock'] ?> disp.</p>
                <?php endif; ?>
            </div>
            <div id="qty-<?= (int)$prod['id_producto'] ?>" style="display:none;flex-direction:column;align-items:center;gap:.2rem;">
                <button onclick="event.stopPropagation();cambiarCant(<?= (int)$prod['id_producto'] ?>,1)" style="width:22px;height:22px;border-radius:6px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;cursor:pointer;font-weight:900;font-size:.8rem;">+</button>
                <span id="cnt-<?= (int)$prod['id_producto'] ?>" style="font-weight:900;font-size:.9rem;color:#1c1917;">1</span>
                <button onclick="event.stopPropagation();cambiarCant(<?= (int)$prod['id_producto'] ?>,-1)" style="width:22px;height:22px;border-radius:6px;border:2px solid #e7e5e4;background:#fff;color:#78716c;cursor:pointer;font-weight:900;font-size:.8rem;">−</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Resumen pedido -->
    <div style="background:#f5f0eb;border-radius:14px;padding:.85rem 1rem;margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.82rem;font-weight:700;color:#78716c;"><i class="fa-solid fa-cart-shopping text-orange-500 mr-1"></i> <span id="np-count">0</span> producto(s)</span>
            <span style="font-size:1.1rem;font-weight:900;color:#ea580c;">$<span id="np-total">0</span></span>
        </div>
    </div>

    <form method="POST" id="formNuevoPedido">
        <input type="hidden" name="accion"          value="crear_pedido">
        <input type="hidden" name="id_tipo_pedido"  id="np-tipo-hidden" value="1">
        <input type="hidden" name="items"           id="np-items-hidden">
        <div style="display:flex;gap:.6rem;">
            <button type="button" onclick="cerrarModal('modalNuevoPedido')" style="flex:1;padding:.8rem;border-radius:14px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">Cancelar</button>
            <button type="submit" id="np-submit" disabled
                style="flex:1;padding:.8rem;border-radius:14px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;cursor:pointer;opacity:.45;transition:opacity .2s;">
                <i class="fa-solid fa-check mr-1"></i> Crear Pedido
            </button>
        </div>
    </form>
</div>
</div>
<div id="modalDetalle"><div class="det-box"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;"><div style="display:flex;align-items:center;gap:.75rem;"><div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-receipt"></i></div><div><h3 id="det-titulo" style="font-size:1.1rem;font-weight:900;color:#1c1917;">Detalle</h3><p id="det-fecha" style="font-size:.78rem;color:#78716c;"></p></div></div><button onclick="cerrarModal('modalDetalle')" style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;"><i class="fa-solid fa-xmark"></i></button></div><div id="det-info" style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1rem;"></div><p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.6rem;padding-bottom:.4rem;border-bottom:1px solid #f5f0eb;"><i class="fa-solid fa-utensils text-orange-500 mr-1"></i> Productos</p><div id="det-productos" style="margin-bottom:1rem;"></div><div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;border-radius:12px;padding:.85rem 1rem;display:flex;justify-content:space-between;align-items:center;"><span style="font-weight:700;color:#78716c;">Total</span><span id="det-total" style="font-size:1.3rem;font-weight:900;color:#ea580c;"></span></div><button onclick="cerrarModal('modalDetalle')" style="width:100%;margin-top:.75rem;padding:.7rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">Cerrar</button></div></div>


<!-- ══ MODAL CAMBIO ESTADO ══ -->
<div id="modalEstado" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:20px;width:100%;max-width:380px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:popIn .22s ease;">
    <h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;margin-bottom:1.25rem;"><i class="fa-solid fa-arrows-rotate text-orange-500 mr-2"></i>Cambiar Estado del Pedido <span id="est-num" style="color:#ea580c;"></span></h3>
    <form method="POST">
        <input type="hidden" name="accion"    value="cambiar_estado">
        <input type="hidden" name="id_pedido" id="est-id">
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.25rem;">
            <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;" onmouseover="this.style.borderColor='#a16207'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_pedido" value="1" style="accent-color:#a16207;">
                <span style="background:#fef9c3;color:#a16207;font-weight:700;font-size:.82rem;padding:.25rem .75rem;border-radius:999px;">Pendiente</span>
            </label>
            <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;" onmouseover="this.style.borderColor='#1d4ed8'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_pedido" value="2" style="accent-color:#1d4ed8;">
                <span style="background:#dbeafe;color:#1d4ed8;font-weight:700;font-size:.82rem;padding:.25rem .75rem;border-radius:999px;">En preparación</span>
            </label>
            <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;" onmouseover="this.style.borderColor='#15803d'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_pedido" value="3" style="accent-color:#15803d;">
                <span style="background:#dcfce7;color:#15803d;font-weight:700;font-size:.82rem;padding:.25rem .75rem;border-radius:999px;">Entregado</span>
            </label>
            <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;border:2px solid #e7e5e4;cursor:pointer;" onmouseover="this.style.borderColor='#b91c1c'" onmouseout="this.style.borderColor='#e7e5e4'">
                <input type="radio" name="id_estado_pedido" value="4" style="accent-color:#b91c1c;">
                <span style="background:#fee2e2;color:#b91c1c;font-weight:700;font-size:.82rem;padding:.25rem .75rem;border-radius:999px;">Cancelado</span>
            </label>
        </div>
        <div style="display:flex;gap:.6rem;">
            <button type="button" onclick="cerrarModal('modalEstado')" style="flex:1;padding:.7rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">Cancelar</button>
            <button type="submit" style="flex:1;padding:.7rem;border-radius:12px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;cursor:pointer;">Guardar</button>
        </div>
    </form>
</div>
</div>

<!-- ══ MODAL NUEVO PEDIDO ══ -->
<div id="modalNuevoPedido" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
<div class="np-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fa-solid fa-plus"></i></div>
            <div><h3 style="font-size:1.15rem;font-weight:900;color:#1c1917;">Nuevo Pedido</h3><p style="font-size:.78rem;color:#78716c;">Selecciona productos y tipo</p></div>
        </div>
        <button onclick="cerrarModal('modalNuevoPedido')" style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div style="margin-bottom:1rem;">
        <p style="font-size:.72rem;font-weight:700;color:#78716c;text-transform:uppercase;margin-bottom:.5rem;">Tipo de pedido</p>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button class="tipo-btn" onclick="selTipo(this,1)" data-tipo="1" style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:999px;border:2px solid #ea580c;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;"><i class="fa-solid fa-chair"></i> Mesa</button>
            <button class="tipo-btn" onclick="selTipo(this,2)" data-tipo="2" style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;"><i class="fa-solid fa-motorcycle"></i> Domicilio</button>
            <button class="tipo-btn" onclick="selTipo(this,3)" data-tipo="3" style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:999px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s;"><i class="fa-solid fa-bag-shopping"></i> Para llevar</button>
        </div>
        <input type="hidden" id="np-tipo" value="1">
    </div>

    <div style="position:relative;margin-bottom:.75rem;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#a8a29e;pointer-events:none;font-size:.85rem;"></i>
        <input type="text" id="np-buscar" placeholder="Buscar producto..." oninput="filtrarProdsNP()"
            style="width:100%;padding:.65rem .9rem .65rem 2.4rem;border:2px solid #e7e5e4;border-radius:12px;font-size:.88rem;outline:none;"
            onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e7e5e4'">
    </div>

    <div id="np-productos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.6rem;max-height:260px;overflow-y:auto;margin-bottom:1rem;padding-right:.2rem;">
        <?php foreach ($productos as $prod):
            $agotado = ($prod['estado_stock'] ?? '') === 'agotado';
        ?>
        <div class="prod-sel-card <?= $agotado?'opacity-40':'' ?>"
             style="<?= $agotado?'cursor:not-allowed;':'' ?>"
             data-id="<?= (int)$prod['id_producto'] ?>"
             data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
             data-precio="<?= (float)$prod['precio'] ?>"
             data-stock="<?= (int)$prod['stock'] ?>"
             data-lower="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>"
             <?= $agotado ? '' : 'onclick="toggleProducto(this)"' ?>>
            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;display:flex;align-items:center;justify-content:center;color:#ea580c;font-size:.8rem;flex-shrink:0;"><i class="fa-solid fa-utensils"></i></div>
            <div style="flex:1;min-width:0;">
                <p style="font-weight:700;font-size:.8rem;color:#1c1917;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($prod['nombre']) ?></p>
                <p style="font-size:.73rem;color:#ea580c;font-weight:700;">$<?= number_format((float)$prod['precio'],0,',','.') ?></p>
                <p style="font-size:.67rem;color:<?= $agotado?'#b91c1c':'#78716c' ?>;"><?= $agotado?'Sin stock':(int)$prod['stock'].' disp.' ?></p>
            </div>
            <div id="qty-<?= (int)$prod['id_producto'] ?>" style="display:none;flex-direction:column;align-items:center;gap:.15rem;">
                <button onclick="event.stopPropagation();cambiarCant(<?= (int)$prod['id_producto'] ?>,1)" style="width:22px;height:22px;border-radius:6px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;cursor:pointer;font-weight:900;font-size:.8rem;line-height:1;">+</button>
                <span id="cnt-<?= (int)$prod['id_producto'] ?>" style="font-weight:900;font-size:.88rem;color:#1c1917;">1</span>
                <button onclick="event.stopPropagation();cambiarCant(<?= (int)$prod['id_producto'] ?>,-1)" style="width:22px;height:22px;border-radius:6px;border:2px solid #e7e5e4;background:#fff;color:#78716c;cursor:pointer;font-weight:900;font-size:.8rem;line-height:1;">−</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="background:#f5f0eb;border-radius:12px;padding:.75rem 1rem;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:.82rem;font-weight:700;color:#78716c;"><i class="fa-solid fa-cart-shopping text-orange-500 mr-1"></i><span id="np-count">0</span> producto(s)</span>
        <span style="font-size:1.1rem;font-weight:900;color:#ea580c;">$<span id="np-total">0</span></span>
    </div>

    <form method="POST" id="formNuevoPedido">
        <input type="hidden" name="accion"         value="crear_pedido">
        <input type="hidden" name="id_tipo_pedido" id="np-tipo-hidden" value="1">
        <input type="hidden" name="items"          id="np-items-hidden">
        <div style="display:flex;gap:.6rem;">
            <button type="button" onclick="cerrarModal('modalNuevoPedido')" style="flex:1;padding:.8rem;border-radius:14px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;">Cancelar</button>
            <button type="submit" id="np-submit" disabled style="flex:1;padding:.8rem;border-radius:14px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;cursor:pointer;opacity:.4;transition:opacity .2s;">
                <i class="fa-solid fa-check mr-1"></i> Crear Pedido
            </button>
        </div>
    </form>
</div>
</div>

<script>
/* ── Modales ── */
function cerrarModal(id) { document.getElementById(id).style.display='none'; document.body.style.overflow=''; }
['modalDetalle','modalEstado','modalNuevoPedido'].forEach(function(id){
    document.getElementById(id).addEventListener('click',function(e){ if(e.target===this) cerrarModal(id); });
});
document.addEventListener('keydown',function(e){ if(e.key==='Escape') ['modalDetalle','modalEstado','modalNuevoPedido'].forEach(cerrarModal); });

/* ── Filtro tabla ── */
function filtrarPedidos() {
    var q=document.getElementById('filtro-ped').value.toLowerCase();
    var est=document.getElementById('filtro-estado-ped').value;
    var vis=0;
    document.querySelectorAll('.fila-ped').forEach(function(f){
        var ok=(f.dataset.texto.includes(q))&&(!est||f.dataset.estado===est);
        f.style.display=ok?'':'none'; if(ok)vis++;
    });
    document.getElementById('sin-ped').style.display=vis===0?'block':'none';
}

/* ── Ver detalle ── */
function verDetalleMesero(id) {
    document.getElementById('det-titulo').textContent='Pedido #'+id;
    document.getElementById('det-fecha').textContent='Cargando...';
    document.getElementById('det-info').innerHTML='';
    document.getElementById('det-productos').innerHTML='<div style="text-align:center;padding:1.5rem;color:#a8a29e;"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i></div>';
    document.getElementById('det-total').textContent='';
    abrirModal('modalDetalle');
    fetch('mesero_pedido_detalle.php?pedido='+id)
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d||d.error){document.getElementById('det-productos').innerHTML='<p style="color:#b91c1c;text-align:center;">'+( d.error||'Error')+'</p>';return;}
            document.getElementById('det-fecha').textContent=d.fecha_pedido?new Date(d.fecha_pedido).toLocaleString('es-CO'):'';
            var estadoColors={'Pendiente':'#fef9c3|#a16207','En preparación':'#dbeafe|#1d4ed8','Entregado':'#dcfce7|#15803d','Cancelado':'#fee2e2|#b91c1c'};
            var ec=(estadoColors[d.estado]||'#f5f0eb|#78716c').split('|');
            document.getElementById('det-info').innerHTML=
                chip('fa-tag','Estado',d.estado||'—',ec[0],ec[1])+
                chip('fa-motorcycle','Tipo',d.tipo||'—','#fff7ed','#ea580c')+
                chip('fa-user','Cliente',d.cliente||'—','#f5f0eb','#78716c')+
                chip('fa-credit-card','Pago',d.metodo_pago||'—','#f5f0eb','#78716c');
            var html='';
            (d.productos||[]).forEach(function(p){
                html+='<div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f5f0eb;">'+
                    '<div style="display:flex;align-items:center;gap:.5rem;">'+
                        '<div style="width:30px;height:30px;border-radius:8px;background:#fff7ed;border:1px solid #fed7aa;display:flex;align-items:center;justify-content:center;color:#ea580c;font-size:.75rem;flex-shrink:0;"><i class="fa-solid fa-utensils"></i></div>'+
                        '<div><p style="font-weight:700;font-size:.85rem;color:#1c1917;">'+p.nombre+'</p><p style="font-size:.72rem;color:#a8a29e;">x'+p.cantidad+' · $'+parseFloat(p.precio_unitario).toLocaleString('es-CO')+' c/u</p></div>'+
                    '</div>'+
                    '<span style="font-weight:900;color:#ea580c;font-size:.88rem;">$'+parseFloat(p.subtotal).toLocaleString('es-CO')+'</span>'+
                '</div>';
            });
            document.getElementById('det-productos').innerHTML=html||'<p style="color:#a8a29e;text-align:center;padding:1rem;">Sin productos</p>';
            document.getElementById('det-total').textContent='$'+parseFloat(d.total_factura||0).toLocaleString('es-CO');
        })
        .catch(function(){document.getElementById('det-productos').innerHTML='<p style="color:#b91c1c;text-align:center;">Error al cargar</p>';});
}
function chip(icon,label,value,bg,color){
    return '<div style="background:'+bg+';border-radius:10px;padding:.55rem .8rem;">'+
        '<p style="font-size:.67rem;font-weight:700;color:#a8a29e;text-transform:uppercase;margin-bottom:.15rem;"><i class="fa-solid '+icon+'" style="color:'+color+';margin-right:.25rem;"></i>'+label+'</p>'+
        '<p style="font-weight:900;font-size:.85rem;color:'+color+';">'+value+'</p>'+
    '</div>';
}

/* ── Cambio estado ── */
function abrirCambioEstado(id, estadoActual) {
    document.getElementById('est-id').value=id;
    document.getElementById('est-num').textContent='#'+id;
    var radios=document.querySelectorAll('[name="id_estado_pedido"]');
    var map={'Pendiente':'1','En preparación':'2','Entregado':'3','Cancelado':'4'};
    radios.forEach(function(r){ r.checked=(r.value===map[estadoActual]); });
    abrirModal('modalEstado');
}

/* ── Nuevo pedido ── */
var npCarrito={};
function abrirNuevoPedido(){
    npCarrito={};
    document.querySelectorAll('.prod-sel-card').forEach(function(c){
        c.classList.remove('en-pedido');
        var qty=document.getElementById('qty-'+c.dataset.id);
        if(qty){qty.style.display='none';}
        var cnt=document.getElementById('cnt-'+c.dataset.id);
        if(cnt){cnt.textContent='1';}
    });
    actualizarResumenNP();
    abrirModal('modalNuevoPedido');
}
function selTipo(btn,tipo){
    document.querySelectorAll('.tipo-btn').forEach(function(b){
        b.style.background='#fff'; b.style.color='#78716c'; b.style.borderColor='#e7e5e4';
    });
    btn.style.background='linear-gradient(135deg,#ea580c,#f59e0b)';
    btn.style.color='#fff'; btn.style.borderColor='#ea580c';
    document.getElementById('np-tipo').value=tipo;
    document.getElementById('np-tipo-hidden').value=tipo;
}
function toggleProducto(card){
    var id=card.dataset.id;
    var qty=document.getElementById('qty-'+id);
    if(npCarrito[id]){
        delete npCarrito[id];
        card.classList.remove('en-pedido');
        if(qty){qty.style.display='none';}
    } else {
        npCarrito[id]={id:parseInt(id),nombre:card.dataset.nombre,precio:parseFloat(card.dataset.precio),cantidad:1,stock:parseInt(card.dataset.stock)};
        card.classList.add('en-pedido');
        if(qty){qty.style.display='flex';}
    }
    actualizarResumenNP();
}
function cambiarCant(id,delta){
    if(!npCarrito[id]) return;
    var nuevo=npCarrito[id].cantidad+delta;
    if(nuevo<=0){ toggleProducto(document.querySelector('[data-id="'+id+'"]')); return; }
    if(nuevo>npCarrito[id].stock){ return; }
    npCarrito[id].cantidad=nuevo;
    var cnt=document.getElementById('cnt-'+id);
    if(cnt) cnt.textContent=nuevo;
    actualizarResumenNP();
}
function actualizarResumenNP(){
    var items=Object.values(npCarrito);
    var total=items.reduce(function(s,i){return s+i.precio*i.cantidad;},0);
    var uds=items.reduce(function(s,i){return s+i.cantidad;},0);
    document.getElementById('np-count').textContent=uds;
    document.getElementById('np-total').textContent=total.toLocaleString('es-CO');
    var btn=document.getElementById('np-submit');
    btn.disabled=uds===0; btn.style.opacity=uds===0?'.4':'1';
    document.getElementById('np-items-hidden').value=JSON.stringify(items);
}
function filtrarProdsNP(){
    var q=document.getElementById('np-buscar').value.toLowerCase();
    document.querySelectorAll('.prod-sel-card').forEach(function(c){
        c.style.display=c.dataset.lower.includes(q)?'':'none';
    });
}
</script>

/* ── Filtros stock ── */
var stkFiltro = 'todos';
function filtrarStock(estado) {}
function buscarStock() {}

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
