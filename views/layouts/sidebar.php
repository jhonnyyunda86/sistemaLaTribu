<?php
$usuario = $_SESSION['usuario'] ?? [];
$rol     = $usuario['role']    ?? 'invitado';
$nombre  = $usuario['nombre']  ?? 'Usuario';
$paginaActual = basename($_SERVER['PHP_SELF']);
?>

<style>
/* ── Sidebar ── */
#sidebar {
    background: linear-gradient(180deg, #1c1917 0%, #0f0c0a 100%);
}
.sidebar-logo-box {
    background: rgba(255,247,237,.05);
    backdrop-filter: blur(10px);
}
.menu-link {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: 12px 16px;
    border-radius: 14px;
    color: #fdba74;
    font-weight: 600;
    font-size: .95rem;
    text-decoration: none;
    transition: all .22s ease;
}
.menu-link i { width: 20px; text-align: center; flex-shrink: 0; }
.menu-link:hover {
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: #fff;
    transform: translateX(5px);
    box-shadow: 0 8px 20px rgba(234,88,12,.35);
}
.menu-link.active {
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: #fff;
    box-shadow: 0 6px 16px rgba(234,88,12,.3);
}
.user-box {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 16px;
    padding: 1rem;
    text-align: center;
}

/* ── Header ── */
#top-header {
    background: linear-gradient(90deg, #ea580c, #f59e0b);
    height: 68px;
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
    box-shadow: 0 4px 20px rgba(234,88,12,.4);
}
</style>

<!-- ══════════════════════════════════════
     SIDEBAR
══════════════════════════════════════ -->
<aside id="sidebar" class="hidden md:flex flex-col text-white shadow-2xl">

    <!-- Logo -->
    <div class="sidebar-logo-box p-6 text-center border-b border-orange-500/20 flex-shrink-0">
        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 flex items-center justify-center shadow-lg">
            <i class="fa-solid fa-utensils text-2xl text-white"></i>
        </div>
        <h2 class="text-xl font-black text-orange-400">La Tribu</h2>
        <p class="text-xs text-orange-200 mt-0.5">Sistema Restaurante</p>
    </div>

    <!-- Usuario -->
    <div class="p-4 flex-shrink-0">
        <div class="user-box">
            <div class="w-11 h-11 mx-auto mb-2 rounded-full bg-orange-500 text-white flex items-center justify-center text-lg font-black">
                <?= strtoupper(substr($nombre, 0, 1)) ?>
            </div>
            <p class="font-bold text-sm text-white"><?= htmlspecialchars($nombre) ?></p>
            <p class="text-xs text-orange-300 mt-0.5"><?= htmlspecialchars(ucfirst($rol)) ?></p>
        </div>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
        <?php if ($rol === 'admin'): ?>
            <a class="menu-link <?= $paginaActual==='admin_dashboard.php'?'active':'' ?>" href="admin_dashboard.php">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
            <a class="menu-link <?= $paginaActual==='admin_menu.php'?'active':'' ?>" href="admin_menu.php">
                <i class="fa-solid fa-utensils"></i> Menú
            </a>
            <a class="menu-link <?= $paginaActual==='admin_mesas.php'?'active':'' ?>" href="admin_mesas.php">
                <i class="fa-solid fa-chair"></i> Mesas
            </a>
            <a class="menu-link <?= $paginaActual==='admin_reservas.php'?'active':'' ?>" href="admin_reservas.php">
                <i class="fa-solid fa-calendar-check"></i> Reservas
            </a>
            <a class="menu-link <?= $paginaActual==='admin_pedidos.php'?'active':'' ?>" href="admin_pedidos.php">
                <i class="fa-solid fa-receipt"></i> Pedidos
            </a>
            <a class="menu-link <?= $paginaActual==='admin_usuarios.php'?'active':'' ?>" href="admin_usuarios.php">
                <i class="fa-solid fa-user-tie"></i> Usuarios
            </a>
            <a class="menu-link <?= $paginaActual==='admin_reportes.php'?'active':'' ?>" href="admin_reportes.php">
                <i class="fa-solid fa-chart-bar"></i> Reportes
            </a>
            <a class="menu-link <?= $paginaActual==='admin_inventario.php'?'active':'' ?>" href="admin_inventario.php">
                <i class="fa-solid fa-boxes-stacked"></i> Inventario
            </a>
        <?php elseif ($rol === 'mesero'): ?>
            <a class="menu-link <?= $paginaActual==='mesero_dashboard.php'?'active':'' ?>" href="mesero_dashboard.php">
                <i class="fa-solid fa-house"></i> Inicio
            </a>
            <a class="menu-link <?= $paginaActual==='mesero_pedidos.php'?'active':'' ?>" href="mesero_pedidos.php">
                <i class="fa-solid fa-receipt"></i> Pedidos
            </a>
            <a class="menu-link <?= $paginaActual==='mesero_reservas.php'?'active':'' ?>" href="mesero_reservas.php">
                <i class="fa-solid fa-calendar-check"></i> Reservas
            </a>
            <a class="menu-link <?= $paginaActual==='mesero_stock.php'?'active':'' ?>" href="mesero_stock.php">
                <i class="fa-solid fa-boxes-stacked"></i> Stock
            </a>
            <a class="menu-link <?= $paginaActual==='admin_mesas.php'?'active':'' ?>" href="admin_mesas.php">
                <i class="fa-solid fa-chair"></i> Mesas
            </a>
        <?php else: ?>
            <a class="menu-link <?= $paginaActual==='cliente_dashboard.php'?'active':'' ?>" href="cliente_dashboard.php">
                <i class="fa-solid fa-burger"></i> Menú
            </a>
            <a class="menu-link <?= $paginaActual==='cliente_reservas.php'?'active':'' ?>" href="cliente_reservas.php">
                <i class="fa-solid fa-calendar-plus"></i> Reservar
            </a>
            <a class="menu-link <?= $paginaActual==='cliente_historial.php'?'active':'' ?>" href="cliente_historial.php">
                <i class="fa-solid fa-clock-rotate-left"></i> Mis Compras
            </a>
            <a class="menu-link <?= $paginaActual==='cliente_cuenta.php'?'active':'' ?>" href="cliente_cuenta.php">
                <i class="fa-solid fa-circle-user"></i> Mi Cuenta
            </a>
        <?php endif; ?>
    </nav>

    <!-- Cerrar sesión -->
    <div class="p-4 flex-shrink-0 border-t border-orange-500/20">
        <a href="../../controllers/AuthController.php?accion=logout"
           class="flex items-center justify-center gap-2 bg-gradient-to-r from-orange-600 to-amber-500 text-white py-2.5 rounded-xl font-bold hover:opacity-90 transition shadow-lg text-sm">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
        </a>
    </div>

</aside>

<!-- ══════════════════════════════════════
     COLUMNA DERECHA (header + contenido + footer)
══════════════════════════════════════ -->
<div id="col-right">

    <!-- Header -->
    <header id="top-header">
        <h1 class="text-xl font-bold tracking-wide truncate">
            <?= htmlspecialchars($titulo) ?>
        </h1>
        <div class="text-right flex-shrink-0">
            <div class="font-black text-sm"><?= htmlspecialchars(ucfirst($rol)) ?></div>
            <div class="text-xs text-orange-100"><?= htmlspecialchars($nombre) ?></div>
        </div>
    </header>

    <!-- Contenido de la página -->
    <div id="content-area">
