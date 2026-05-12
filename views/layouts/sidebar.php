<?php
$usuario = $_SESSION['usuario'] ?? [];
$rol     = $usuario['role']    ?? 'invitado';
$nombre  = $usuario['nombre']  ?? 'Usuario';

// Página actual para marcar el link activo
$paginaActual = basename($_SERVER['PHP_SELF']);
?>

<style>
.sidebar-bg {
    background: linear-gradient(180deg, #1c1917 0%, #0f0c0a 100%);
}
.glass-header {
    background: rgba(255,247,237,.05);
    backdrop-filter: blur(10px);
}
.menu {
    display: flex;
    gap: .9rem;
    align-items: center;
    padding: 13px 16px;
    border-radius: 14px;
    color: #fdba74;
    font-weight: 600;
    font-size: .95rem;
    transition: all .22s ease;
    text-decoration: none;
}
.menu i { width: 22px; text-align: center; }
.menu:hover {
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: white;
    transform: translateX(5px);
    box-shadow: 0 8px 20px rgba(234,88,12,.35);
}
.menu.active {
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: white;
    box-shadow: 0 8px 20px rgba(234,88,12,.3);
}
.user-box {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.08);
}
</style>

<!-- ══ SIDEBAR ══ -->
<aside id="sidebar" class="sidebar-bg text-white shadow-2xl hidden md:flex flex-col">

    <!-- Logo -->
    <div class="p-6 text-center glass-header border-b border-orange-500/20 flex-shrink-0">
        <div class="w-20 h-20 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 flex items-center justify-center shadow-lg">
            <i class="fa-solid fa-utensils text-3xl text-white"></i>
        </div>
        <h2 class="text-2xl font-black text-orange-400">La Tribu</h2>
        <p class="text-xs text-orange-200 mt-1">Sistema Restaurante</p>
    </div>

    <!-- Usuario -->
    <div class="p-4 flex-shrink-0">
        <div class="user-box rounded-2xl p-4 text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-orange-500 text-white flex items-center justify-center text-xl font-bold">
                <?= strtoupper(substr($nombre, 0, 1)) ?>
            </div>
            <p class="font-bold text-sm"><?= htmlspecialchars($nombre) ?></p>
            <p class="text-xs text-orange-300"><?= htmlspecialchars(ucfirst($rol)) ?></p>
        </div>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-4 space-y-1 overflow-y-auto">

        <?php if ($rol === 'admin'): ?>

            <a class="menu <?= $paginaActual==='admin_dashboard.php' ?'active':'' ?>" href="admin_dashboard.php">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
            <a class="menu <?= $paginaActual==='admin_menu.php' ?'active':'' ?>" href="admin_menu.php">
                <i class="fa-solid fa-utensils"></i> Menú
            </a>
            <a class="menu <?= $paginaActual==='admin_mesas.php' ?'active':'' ?>" href="admin_mesas.php">
                <i class="fa-solid fa-chair"></i> Mesas
            </a>
            <a class="menu <?= $paginaActual==='admin_reservas.php' ?'active':'' ?>" href="admin_reservas.php">
                <i class="fa-solid fa-calendar-check"></i> Reservas
            </a>
            <a class="menu <?= $paginaActual==='admin_pedidos.php' ?'active':'' ?>" href="admin_pedidos.php">
                <i class="fa-solid fa-receipt"></i> Pedidos
            </a>
            <a class="menu <?= $paginaActual==='admin_usuarios.php' ?'active':'' ?>" href="admin_usuarios.php">
                <i class="fa-solid fa-user-tie"></i> Usuarios
            </a>
            <a class="menu <?= $paginaActual==='admin_reportes.php' ?'active':'' ?>" href="admin_reportes.php">
                <i class="fa-solid fa-chart-bar"></i> Reportes
            </a>

        <?php elseif ($rol === 'mesero'): ?>

            <a class="menu <?= $paginaActual==='mesero_dashboard.php' ?'active':'' ?>" href="mesero_dashboard.php">
                <i class="fa-solid fa-clipboard-list"></i> Pedidos
            </a>
            <a class="menu <?= $paginaActual==='admin_mesas.php' ?'active':'' ?>" href="admin_mesas.php">
                <i class="fa-solid fa-chair"></i> Mesas
            </a>

        <?php else: ?>

            <a class="menu <?= $paginaActual==='cliente_dashboard.php' ?'active':'' ?>" href="cliente_dashboard.php">
                <i class="fa-solid fa-burger"></i> Menú
            </a>
            <a class="menu <?= $paginaActual==='cliente_reservas.php' ?'active':'' ?>" href="cliente_reservas.php">
                <i class="fa-solid fa-calendar-plus"></i> Reservar
            </a>

        <?php endif; ?>

    </nav>

    <!-- Cerrar sesión -->
    <div class="p-4 flex-shrink-0 border-t border-orange-500/20">
        <a href="../../controllers/AuthController.php?accion=logout"
           class="flex items-center justify-center gap-2 bg-gradient-to-r from-orange-600 to-amber-500 text-white py-3 rounded-xl font-bold hover:opacity-90 hover:scale-[1.02] transition shadow-lg">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
        </a>
    </div>

</aside>

<!-- ══ COLUMNA DERECHA ══ -->
<div id="col-right">

    <!-- Header fijo -->
    <header id="top-header" class="bg-gradient-to-r from-orange-600 to-amber-500 h-20 px-8 flex justify-between items-center text-white shadow-xl">
        <h1 class="text-2xl font-bold tracking-wide">
            <?= htmlspecialchars($titulo) ?>
        </h1>
        <div class="text-right">
            <div class="font-black text-sm"><?= htmlspecialchars(ucfirst($rol)) ?></div>
            <div class="text-xs text-orange-100"><?= htmlspecialchars($nombre) ?></div>
        </div>
    </header>

    <!-- Contenido scrolleable -->
    <div id="content-area">
