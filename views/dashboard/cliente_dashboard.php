<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'cliente') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Reserva.php';   // para obtenerIdCliente

$db        = (new Database())->conectar();
$prodModel = new Producto($db);
$resModel  = new Reserva($db);

$nombre    = $_SESSION['usuario']['nombre'] ?? 'Cliente';
$idUsuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$productos = $prodModel->obtenerMenuCliente();
$categorias= $prodModel->obtenerCategorias();
$titulo    = 'Menú';

/* ══════════════════════════════════════════
   PROCESAR PEDIDO (POST)
══════════════════════════════════════════ */
$pedidoOk    = false;
$pedidoError = '';
$pedidoNum   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'realizar_pedido') {
    $metodoPago = trim($_POST['metodo_pago'] ?? '');
    $itemsJson  = trim($_POST['items']       ?? '');
    $items      = json_decode($itemsJson, true);

    if (empty($items) || !is_array($items)) {
        $pedidoError = 'El carrito está vacío.';
    } elseif (!$metodoPago) {
        $pedidoError = 'Selecciona un método de pago.';
    } else {
        try {
            $db->beginTransaction();

            // 1. Obtener o crear id_cliente
            $idCliente = $resModel->obtenerIdCliente($idUsuario);
            if (!$idCliente) {
                $tel = $_SESSION['usuario']['telefono'] ?? '';
                $idCliente = $resModel->crearCliente($idUsuario, $tel);
            }

            // 2. Crear pedido (tipo 2 = Domicilio, estado 1 = Pendiente)
            $sqlPed = "INSERT INTO pedido (id_cliente, id_tipo_pedido, id_estado_pedido, fecha_pedido)
                       VALUES (:ic, 2, 1, NOW())";
            $stmtPed = $db->prepare($sqlPed);
            $stmtPed->execute([':ic' => $idCliente]);
            $idPedido = (int)$db->lastInsertId();

            // 3. Insertar detalles y validar stock
            foreach ($items as $item) {
                $idProd   = (int)($item['id']       ?? 0);
                $cantidad = (int)($item['cantidad']  ?? 0);
                $precio   = (float)($item['precio'] ?? 0);

                if ($idProd <= 0 || $cantidad <= 0) continue;

                // Verificar stock
                $sqlStk = "SELECT COALESCE(cantidad_actual,0) FROM inventario WHERE id_producto = :id LIMIT 1";
                $stmtStk = $db->prepare($sqlStk);
                $stmtStk->execute([':id' => $idProd]);
                $stockDisp = (int)$stmtStk->fetchColumn();

                if ($stockDisp < $cantidad) {
                    throw new \Exception("Stock insuficiente para uno de los productos.");
                }

                $subtotal = $precio * $cantidad;
                $sqlDet = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                           VALUES (:ip, :iprod, :cant, :pu, :sub)";
                $stmtDet = $db->prepare($sqlDet);
                $stmtDet->execute([
                    ':ip'   => $idPedido,
                    ':iprod'=> $idProd,
                    ':cant' => $cantidad,
                    ':pu'   => $precio,
                    ':sub'  => $subtotal,
                ]);
            }

            // 4. Calcular total
            $sqlTot = "SELECT SUM(subtotal) FROM detalle_pedido WHERE id_pedido = :id";
            $stmtTot = $db->prepare($sqlTot);
            $stmtTot->execute([':id' => $idPedido]);
            $totalPedido = (float)$stmtTot->fetchColumn();

            // 5. Crear factura
            $sqlFac = "INSERT INTO factura (id_pedido, id_cliente, fecha, metodo_pago, total_factura)
                       VALUES (:ip, :ic, CURDATE(), :mp, :tot)";
            $stmtFac = $db->prepare($sqlFac);
            $stmtFac->execute([
                ':ip'  => $idPedido,
                ':ic'  => $idCliente,
                ':mp'  => $metodoPago,
                ':tot' => $totalPedido,
            ]);

            $db->commit();
            $pedidoOk  = true;
            $pedidoNum = $idPedido;

        } catch (\Exception $e) {
            $db->rollBack();
            $pedidoError = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<style>
.cat-btn { display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1.1rem;border-radius:999px;font-weight:700;font-size:.85rem;border:2px solid #e7e5e4;background:#fff;color:#78716c;cursor:pointer;transition:all .2s;white-space:nowrap; }
.cat-btn:hover  { border-color:#fdba74;color:#ea580c;background:#fff7ed; }
.cat-btn.activo { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;border-color:transparent;box-shadow:0 4px 14px rgba(234,88,12,.3); }
.prod-card { background:#fff;border-radius:20px;overflow:hidden;border:2px solid #f5f0eb;transition:transform .22s,box-shadow .22s,border-color .22s;display:flex;flex-direction:column; }
.prod-card:hover { transform:translateY(-5px);box-shadow:0 16px 40px rgba(234,88,12,.15);border-color:#fdba74; }
.prod-card.agotado { opacity:.65;filter:grayscale(.4); }
.badge-disponible { background:#dcfce7;color:#15803d; }
.badge-bajo       { background:#fef9c3;color:#a16207; }
.badge-agotado    { background:#fee2e2;color:#b91c1c; }
.btn-agregar { width:100%;padding:.7rem;border-radius:12px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;font-size:.9rem;cursor:pointer;transition:opacity .2s,transform .15s;box-shadow:0 4px 14px rgba(234,88,12,.25); }
.btn-agregar:hover:not(:disabled) { opacity:.9;transform:scale(1.02); }
.btn-agregar:disabled { background:#e7e5e4;color:#a8a29e;box-shadow:none;cursor:not-allowed; }
#carrito-fab { position:fixed;bottom:2rem;right:2rem;z-index:999;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-size:1.4rem;border:none;cursor:pointer;box-shadow:0 8px 24px rgba(234,88,12,.45);display:flex;align-items:center;justify-content:center;transition:transform .2s; }
#carrito-fab:hover { transform:scale(1.1); }
#carrito-badge { position:absolute;top:-4px;right:-4px;background:#dc2626;color:#fff;font-size:.65rem;font-weight:900;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff; }
#modalCarrito,#modalPago,#modalConfirmacion { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:flex-end;justify-content:center; }
@media(min-width:640px){ #modalCarrito,#modalPago,#modalConfirmacion { align-items:center; } }
.carrito-box,.pago-box,.confirm-box { background:#fff;border-radius:24px 24px 0 0;width:100%;max-width:520px;max-height:88vh;overflow-y:auto;padding:1.5rem;animation:slideUp .3s ease; }
@media(min-width:640px){ .carrito-box,.pago-box,.confirm-box { border-radius:24px; } }
.metodo-btn { display:flex;align-items:center;gap:1rem;padding:1rem 1.25rem;border-radius:16px;border:2px solid #e7e5e4;background:#fff;cursor:pointer;transition:all .2s;width:100%;text-align:left; }
.metodo-btn:hover { border-color:#fdba74;background:#fff7ed; }
.metodo-btn.seleccionado { border-color:#ea580c;background:linear-gradient(135deg,#fff7ed,#ffedd5);box-shadow:0 0 0 3px rgba(234,88,12,.12); }
.metodo-ico { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0; }
@keyframes slideUp { from{transform:translateY(60px);opacity:0} to{transform:translateY(0);opacity:1} }
@keyframes popIn   { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }
</style>

<?php if ($pedidoOk): ?>
<div style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:1.25rem 1.5rem;border-radius:18px;display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;box-shadow:0 6px 20px rgba(34,197,94,.3);">
    <i class="fa-solid fa-circle-check" style="font-size:2rem;flex-shrink:0;"></i>
    <div>
        <p style="font-weight:900;font-size:1.05rem;">¡Pedido #<?= $pedidoNum ?> realizado con éxito!</p>
        <p style="font-size:.85rem;opacity:.9;margin-top:.2rem;">Tu pedido a domicilio está en camino. Pronto nos comunicaremos contigo.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($pedidoError): ?>
<div style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:1rem 1.5rem;border-radius:16px;display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;font-weight:700;">
    <i class="fa-solid fa-circle-exclamation text-xl"></i> <?= htmlspecialchars($pedidoError) ?>
</div>
<?php endif; ?>

<!-- BIENVENIDA -->
<div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:24px;padding:1.5rem 2rem;margin-bottom:1.5rem;">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-orange-300 font-bold uppercase tracking-widest text-xs mb-1">Restaurante La Tribu</p>
            <h1 class="text-3xl font-black text-white">Hola, <?= htmlspecialchars($nombre) ?> 👋</h1>
            <p class="text-orange-200 text-sm mt-1">Explora nuestro menú y haz tu pedido a domicilio</p>
        </div>
        <a href="cliente_reservas.php" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white font-black px-5 py-2.5 rounded-2xl transition shadow-lg text-sm flex-shrink-0">
            <i class="fa-solid fa-calendar-plus"></i> Reservar mesa
        </a>
    </div>
</div>

<!-- BUSCADOR + FILTROS -->
<div style="background:rgba(255,247,237,.95);border:1px solid rgba(251,146,60,.18);border-radius:20px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
    <div style="position:relative;margin-bottom:1rem;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#a8a29e;pointer-events:none;"></i>
        <input type="text" id="buscador" placeholder="Buscar en el menú..." oninput="filtrarProductos()"
            style="width:100%;padding:.75rem 1rem .75rem 2.75rem;border:2px solid #e7e5e4;border-radius:14px;font-size:.95rem;outline:none;background:#fff;transition:border-color .2s;">
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
        <button class="cat-btn activo" onclick="filtrarCategoria('todos',this)">
            <i class="fa-solid fa-border-all"></i> Todos
            <span style="background:rgba(255,255,255,.25);border-radius:999px;padding:.1rem .5rem;font-size:.75rem;"><?= count($productos) ?></span>
        </button>
        <?php
        $iconosCat = ['Comidas rápidas'=>'fa-burger','Bebidas'=>'fa-wine-glass','Parrilla'=>'fa-fire-flame-curved','Postres'=>'fa-ice-cream','Combos'=>'fa-box-open','Entradas'=>'fa-leaf'];
        foreach ($categorias as $cat):
            if ((int)$cat['total'] === 0) continue;
            $icono = $iconosCat[$cat['nombre_categoria']] ?? 'fa-utensils';
            $slug  = strtolower(preg_replace('/\s+/','-',$cat['nombre_categoria']));
        ?>
        <button class="cat-btn" onclick="filtrarCategoria('<?= $slug ?>',this)">
            <i class="fa-solid <?= $icono ?>"></i> <?= htmlspecialchars($cat['nombre_categoria']) ?>
            <span style="background:#f5f0eb;border-radius:999px;padding:.1rem .5rem;font-size:.75rem;color:#78716c;"><?= (int)$cat['total'] ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- GRID PRODUCTOS -->
<div id="gridProductos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.25rem;">
<?php if (empty($productos)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:4rem;color:#a8a29e;">
        <i class="fa-solid fa-utensils" style="font-size:3rem;display:block;margin-bottom:1rem;color:#d6d3d1;"></i>
        <p style="font-size:1.1rem;font-weight:700;">No hay productos disponibles</p>
    </div>
<?php else: ?>
    <?php foreach ($productos as $p):
        $stock=$p['stock']; $estado=$p['estado_stock']; $agotado=$estado==='agotado';
        $catSlug=strtolower(preg_replace('/\s+/','-',$p['nombre_categoria']??''));
        $badgeLabel=match($estado){'agotado'=>'✕ Agotado','bajo'=>'⚠ Stock bajo',default=>'✓ Disponible'};
        $stockLabel=match($estado){'agotado'=>'Sin stock','bajo'=>$stock.' restantes',default=>$stock.' disponibles'};
        $stockColor=$agotado?'#b91c1c':($estado==='bajo'?'#a16207':'#15803d');
        $stockIcon=$agotado?'circle-xmark':($estado==='bajo'?'triangle-exclamation':'circle-check');
        $imgFallback=match($p['nombre_categoria']??''){'Bebidas'=>'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&q=80','Parrilla'=>'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&q=80','Postres'=>'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400&q=80','Combos'=>'https://images.unsplash.com/photo-1561758033-d89a9ad46330?w=400&q=80','Entradas'=>'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&q=80',default=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80'};
        // Si la imagen es un archivo local (no empieza con http), usar ruta local
        if (!empty($p['imagen'])) {
            $imgVal = $p['imagen'];
            if (str_starts_with($imgVal, 'http')) {
                $imgSrc = htmlspecialchars($imgVal);
            } else {
                $rutaFisica = __DIR__ . '/../../img/productos/' . $imgVal;
                $imgSrc = file_exists($rutaFisica)
                    ? '../../img/productos/' . htmlspecialchars($imgVal) . '?v=' . filemtime($rutaFisica)
                    : $imgFallback;
            }
        } else {
            $imgSrc = $imgFallback;
        }
    ?>
    <div class="prod-card <?= $agotado?'agotado':'' ?>" data-cat="<?= $catSlug ?>" data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>" data-id="<?= (int)$p['id_producto'] ?>" data-precio="<?= (float)$p['precio'] ?>" data-stock="<?= (int)$stock ?>">
        <div style="position:relative;height:180px;overflow:hidden;">
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" style="width:100%;height:180px;object-fit:cover;">
            <div style="display:none;width:100%;height:180px;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff7ed,#ffedd5);font-size:3rem;">🍽️</div>
            <?php if($p['nombre_categoria']): ?><div style="position:absolute;top:.6rem;left:.6rem;background:rgba(28,25,23,.75);backdrop-filter:blur(6px);color:#fdba74;font-size:.7rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;"><?= htmlspecialchars($p['nombre_categoria']) ?></div><?php endif; ?>
            <div style="position:absolute;top:.6rem;right:.6rem;"><span class="badge-<?= $estado ?>" style="display:inline-flex;align-items:center;font-size:.7rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;"><?= $badgeLabel ?></span></div>
            <?php if($agotado): ?><div style="position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;"><span style="background:#dc2626;color:#fff;font-weight:900;font-size:1rem;padding:.5rem 1.25rem;border-radius:12px;">AGOTADO</span></div><?php endif; ?>
        </div>
        <div style="padding:1rem;flex:1;display:flex;flex-direction:column;gap:.5rem;">
            <h3 style="font-size:1rem;font-weight:900;color:#1c1917;line-height:1.3;"><?= htmlspecialchars($p['nombre']) ?></h3>
            <?php if(!empty($p['descripcion'])): ?><p style="font-size:.8rem;color:#78716c;line-height:1.4;flex:1;"><?= htmlspecialchars($p['descripcion']) ?></p><?php endif; ?>
            <div style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:600;color:<?= $stockColor ?>;"><i class="fa-solid fa-<?= $stockIcon ?>"></i><?= $stockLabel ?></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.25rem;">
                <span style="font-size:1.3rem;font-weight:900;color:#ea580c;">$<?= number_format((float)$p['precio'],0,',','.') ?></span>
                <button class="btn-agregar" style="width:auto;padding:.5rem 1rem;font-size:.82rem;" <?= $agotado?'disabled':'' ?> onclick="agregarAlCarrito(<?= (int)$p['id_producto'] ?>,'<?= htmlspecialchars(addslashes($p['nombre'])) ?>',<?= (float)$p['precio'] ?>,<?= (int)$stock ?>)">
                    <?= $agotado?'<i class="fa-solid fa-ban"></i> Agotado':'<i class="fa-solid fa-plus"></i> Agregar' ?>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<div id="sinResultados" style="display:none;text-align:center;padding:3rem;color:#a8a29e;">
    <i class="fa-solid fa-magnifying-glass" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#d6d3d1;"></i>
    <p style="font-size:1rem;font-weight:700;">No se encontraron productos</p>
</div>

<!-- FAB CARRITO -->
<button id="carrito-fab" onclick="abrirCarrito()" title="Ver carrito">
    <i class="fa-solid fa-cart-shopping"></i>
    <span id="carrito-badge" style="display:none;">0</span>
</button>

<!-- ══ MODAL CARRITO ══ -->
<div id="modalCarrito">
<div class="carrito-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;"><i class="fa-solid fa-cart-shopping"></i></div>
            <div><h3 style="font-size:1.1rem;font-weight:900;color:#1c1917;">Tu Pedido</h3><p id="carrito-subtitulo" style="font-size:.78rem;color:#78716c;">0 productos</p></div>
        </div>
        <button onclick="cerrarCarrito()" style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div id="carrito-items" style="min-height:80px;margin-bottom:1rem;">
        <div id="carrito-vacio" style="text-align:center;padding:2rem;color:#a8a29e;">
            <i class="fa-solid fa-cart-shopping" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#d6d3d1;"></i>
            <p style="font-weight:700;">Tu carrito está vacío</p>
            <p style="font-size:.82rem;margin-top:.25rem;">Agrega productos del menú</p>
        </div>
    </div>

    <div id="carrito-footer" style="display:none;border-top:2px solid #f5f0eb;padding-top:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <span style="font-weight:700;color:#78716c;font-size:.95rem;">Total del pedido</span>
            <span id="carrito-total" style="font-size:1.5rem;font-weight:900;color:#ea580c;">$0</span>
        </div>

        <!-- BOTÓN REALIZAR PEDIDO -->
        <button onclick="cerrarCarrito();abrirPago();"
            style="width:100%;padding:1rem;border-radius:16px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;font-size:1rem;cursor:pointer;box-shadow:0 6px 20px rgba(234,88,12,.35);display:flex;align-items:center;justify-content:center;gap:.6rem;transition:opacity .2s;"
            onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
            <i class="fa-solid fa-bag-shopping"></i> Realizar Pedido a Domicilio
        </button>

        <button onclick="vaciarCarrito()" style="width:100%;margin-top:.6rem;padding:.65rem;border-radius:12px;border:2px solid #fee2e2;background:#fff;color:#dc2626;font-weight:700;cursor:pointer;font-size:.85rem;transition:background .2s;" onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background='#fff'">
            <i class="fa-solid fa-trash"></i> Vaciar carrito
        </button>
    </div>
</div>
</div>

<!-- ══ MODAL PAGO ══ -->
<div id="modalPago">
<div class="pago-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="fa-solid fa-credit-card"></i></div>
            <div><h3 style="font-size:1.15rem;font-weight:900;color:#1c1917;">Método de Pago</h3><p style="font-size:.78rem;color:#78716c;">Selecciona cómo quieres pagar</p></div>
        </div>
        <button onclick="cerrarPago()" style="background:#f5f5f4;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#78716c;" onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626'" onmouseout="this.style.background='#f5f5f4';this.style.color='#78716c'"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Resumen rápido -->
    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:.85rem 1rem;margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:.85rem;color:#78716c;font-weight:600;"><i class="fa-solid fa-receipt text-orange-500 mr-1"></i> Total a pagar</div>
        <div id="pago-total" style="font-size:1.3rem;font-weight:900;color:#ea580c;">$0</div>
    </div>

    <!-- Métodos de pago -->
    <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:1.5rem;" id="metodos-container">
        <button class="metodo-btn" onclick="seleccionarMetodo(this,'Efectivo')">
            <div class="metodo-ico" style="background:#dcfce7;color:#15803d;"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div><p style="font-weight:800;color:#1c1917;font-size:.95rem;">Efectivo</p><p style="font-size:.78rem;color:#78716c;">Paga al recibir tu pedido</p></div>
            <i class="fa-solid fa-circle-check ml-auto" style="color:#e7e5e4;font-size:1.2rem;" id="check-Efectivo"></i>
        </button>
        <button class="metodo-btn" onclick="seleccionarMetodo(this,'Tarjeta')">
            <div class="metodo-ico" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-credit-card"></i></div>
            <div><p style="font-weight:800;color:#1c1917;font-size:.95rem;">Tarjeta débito/crédito</p><p style="font-size:.78rem;color:#78716c;">Paga con tu tarjeta al recibir</p></div>
            <i class="fa-solid fa-circle-check ml-auto" style="color:#e7e5e4;font-size:1.2rem;" id="check-Tarjeta"></i>
        </button>
        <button class="metodo-btn" onclick="seleccionarMetodo(this,'Nequi')">
            <div class="metodo-ico" style="background:#fae8ff;color:#a21caf;"><i class="fa-solid fa-mobile-screen"></i></div>
            <div><p style="font-weight:800;color:#1c1917;font-size:.95rem;">Nequi</p><p style="font-size:.78rem;color:#78716c;">Transferencia por Nequi</p></div>
            <i class="fa-solid fa-circle-check ml-auto" style="color:#e7e5e4;font-size:1.2rem;" id="check-Nequi"></i>
        </button>
        <button class="metodo-btn" onclick="seleccionarMetodo(this,'Daviplata')">
            <div class="metodo-ico" style="background:#fef9c3;color:#a16207;"><i class="fa-solid fa-wallet"></i></div>
            <div><p style="font-weight:800;color:#1c1917;font-size:.95rem;">Daviplata</p><p style="font-size:.78rem;color:#78716c;">Transferencia por Daviplata</p></div>
            <i class="fa-solid fa-circle-check ml-auto" style="color:#e7e5e4;font-size:1.2rem;" id="check-Daviplata"></i>
        </button>
        <button class="metodo-btn" onclick="seleccionarMetodo(this,'Transferencia')">
            <div class="metodo-ico" style="background:#e0f2fe;color:#0369a1;"><i class="fa-solid fa-building-columns"></i></div>
            <div><p style="font-weight:800;color:#1c1917;font-size:.95rem;">Transferencia bancaria</p><p style="font-size:.78rem;color:#78716c;">Transferencia a cuenta del restaurante</p></div>
            <i class="fa-solid fa-circle-check ml-auto" style="color:#e7e5e4;font-size:1.2rem;" id="check-Transferencia"></i>
        </button>
    </div>

    <!-- Formulario oculto -->
    <form method="POST" id="formPedido">
        <input type="hidden" name="accion"      value="realizar_pedido">
        <input type="hidden" name="metodo_pago" id="input-metodo">
        <input type="hidden" name="items"       id="input-items">

        <button type="submit" id="btn-confirmar-pago" disabled
            style="width:100%;padding:1rem;border-radius:16px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;font-weight:900;font-size:1rem;cursor:pointer;box-shadow:0 6px 20px rgba(234,88,12,.35);display:flex;align-items:center;justify-content:center;gap:.6rem;transition:opacity .2s;opacity:.45;"
            onmouseover="if(!this.disabled)this.style.opacity='.9'" onmouseout="if(!this.disabled)this.style.opacity='1'">
            <i class="fa-solid fa-check-circle"></i> Confirmar Pedido
        </button>
    </form>

    <button onclick="cerrarPago();abrirCarrito();" style="width:100%;margin-top:.6rem;padding:.65rem;border-radius:12px;border:2px solid #e7e5e4;background:#fff;color:#78716c;font-weight:700;cursor:pointer;font-size:.85rem;">
        <i class="fa-solid fa-arrow-left"></i> Volver al carrito
    </button>
</div>
</div>
<script>
/* ── Filtros ── */
var catActiva = 'todos';
function filtrarCategoria(cat, btn) {
    catActiva = cat;
    document.querySelectorAll('.cat-btn').forEach(function(b){ b.classList.remove('activo'); });
    btn.classList.add('activo');
    filtrarProductos();
}
function filtrarProductos() {
    var q = (document.getElementById('buscador').value||'').toLowerCase();
    var vis = 0;
    document.querySelectorAll('.prod-card').forEach(function(c){
        var ok = (catActiva==='todos'||c.dataset.cat===catActiva) && c.dataset.nombre.includes(q);
        c.style.display = ok ? '' : 'none';
        if(ok) vis++;
    });
    document.getElementById('sinResultados').style.display = vis===0 ? 'block' : 'none';
}

/* ── Carrito ── */
var carrito = {};
function agregarAlCarrito(id, nombre, precio, stock) {
    if (carrito[id]) {
        if (carrito[id].cantidad >= stock) { mostrarToast('No hay más stock disponible','error'); return; }
        carrito[id].cantidad++;
    } else {
        carrito[id] = {id:id, nombre:nombre, precio:precio, cantidad:1, stock:stock};
    }
    actualizarCarritoUI();
    mostrarToast(nombre + ' agregado','ok');
}
function quitarDelCarrito(id) {
    if (carrito[id]) { carrito[id].cantidad--; if(carrito[id].cantidad<=0) delete carrito[id]; }
    actualizarCarritoUI();
}
function vaciarCarrito() { carrito={}; actualizarCarritoUI(); }

function actualizarCarritoUI() {
    var items = Object.values(carrito);
    var total = items.reduce(function(s,i){ return s+i.precio*i.cantidad; },0);
    var uds   = items.reduce(function(s,i){ return s+i.cantidad; },0);
    var badge = document.getElementById('carrito-badge');
    badge.textContent = uds;
    badge.style.display = uds>0 ? 'flex' : 'none';
    document.getElementById('carrito-subtitulo').textContent = uds+' producto'+(uds!==1?'s':'');
    var contenedor = document.getElementById('carrito-items');
    var vacio      = document.getElementById('carrito-vacio');
    var footer     = document.getElementById('carrito-footer');
    if (items.length===0) {
        vacio.style.display='block'; footer.style.display='none';
        contenedor.innerHTML=''; contenedor.appendChild(vacio); return;
    }
    vacio.style.display='none'; footer.style.display='block';
    var html='';
    items.forEach(function(item){
        html+='<div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid #f5f0eb;">'+
            '<div style="flex:1;">'+
                '<p style="font-weight:700;font-size:.9rem;color:#1c1917;">'+item.nombre+'</p>'+
                '<p style="font-size:.8rem;color:#ea580c;font-weight:700;">$'+(item.precio*item.cantidad).toLocaleString('es-CO')+'</p>'+
            '</div>'+
            '<div style="display:flex;align-items:center;gap:.4rem;">'+
                '<button onclick="quitarDelCarrito('+item.id+')" style="width:28px;height:28px;border-radius:8px;border:2px solid #e7e5e4;background:#fff;cursor:pointer;font-weight:900;font-size:.9rem;color:#78716c;" onmouseover="this.style.borderColor=\'#ea580c\';this.style.color=\'#ea580c\'" onmouseout="this.style.borderColor=\'#e7e5e4\';this.style.color=\'#78716c\'">−</button>'+
                '<span style="font-weight:900;font-size:1rem;min-width:24px;text-align:center;">'+item.cantidad+'</span>'+
                '<button onclick="agregarAlCarrito('+item.id+',\''+item.nombre.replace(/\'/g,"\\'")+'\','+item.precio+','+item.stock+')" style="width:28px;height:28px;border-radius:8px;border:none;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;cursor:pointer;font-weight:900;font-size:.9rem;">+</button>'+
            '</div>'+
        '</div>';
    });
    contenedor.innerHTML=html;
    document.getElementById('carrito-total').textContent='$'+total.toLocaleString('es-CO');
}

function abrirCarrito()  { document.getElementById('modalCarrito').style.display='flex'; document.body.style.overflow='hidden'; }
function cerrarCarrito() { document.getElementById('modalCarrito').style.display='none'; document.body.style.overflow=''; }
document.getElementById('modalCarrito').addEventListener('click',function(e){ if(e.target===this) cerrarCarrito(); });

/* ── Pago ── */
var metodoPagoSeleccionado = '';
function abrirPago() {
    var items = Object.values(carrito);
    var total = items.reduce(function(s,i){ return s+i.precio*i.cantidad; },0);
    document.getElementById('pago-total').textContent = '$'+total.toLocaleString('es-CO');
    document.getElementById('modalPago').style.display='flex';
    document.body.style.overflow='hidden';
}
function cerrarPago() { document.getElementById('modalPago').style.display='none'; document.body.style.overflow=''; }
document.getElementById('modalPago').addEventListener('click',function(e){ if(e.target===this) cerrarPago(); });

function seleccionarMetodo(btn, metodo) {
    metodoPagoSeleccionado = metodo;
    document.querySelectorAll('.metodo-btn').forEach(function(b){ b.classList.remove('seleccionado'); });
    document.querySelectorAll('[id^="check-"]').forEach(function(i){ i.style.color='#e7e5e4'; });
    btn.classList.add('seleccionado');
    document.getElementById('check-'+metodo).style.color='#ea580c';
    document.getElementById('input-metodo').value = metodo;
    document.getElementById('input-items').value  = JSON.stringify(Object.values(carrito));
    var btnConf = document.getElementById('btn-confirmar-pago');
    btnConf.disabled = false;
    btnConf.style.opacity = '1';
    btnConf.style.cursor  = 'pointer';
}

/* ── Escape ── */
document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){ cerrarCarrito(); cerrarPago(); }
});

/* ── Toast ── */
function mostrarToast(msg, tipo) {
    var t=document.createElement('div');
    t.textContent=msg;
    t.style.cssText='position:fixed;bottom:6rem;left:50%;transform:translateX(-50%);background:'+(tipo==='ok'?'linear-gradient(135deg,#22c55e,#16a34a)':'linear-gradient(135deg,#ef4444,#dc2626)')+';color:#fff;font-weight:700;font-size:.85rem;padding:.6rem 1.25rem;border-radius:999px;box-shadow:0 4px 16px rgba(0,0,0,.2);z-index:99999;white-space:nowrap;animation:popIn .2s ease;';
    document.body.appendChild(t);
    setTimeout(function(){ t.remove(); },2200);
}

<?php if($pedidoOk): ?>
// Limpiar carrito tras pedido exitoso
carrito = {};
actualizarCarritoUI();
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
