<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'mesero') {
    header('Location: ../usuarios/login.php'); exit;
}
$titulo = 'Panel Mesero';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$nombre = $_SESSION['usuario']['nombre'] ?? 'Mesero';
?>

<div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:28px;padding:2rem;" class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <p class="text-orange-300 font-bold uppercase tracking-wide text-sm mb-2">Panel de servicio</p>
            <h1 class="text-4xl font-black text-white">Bienvenido, <?= htmlspecialchars($nombre) ?></h1>
            <p class="text-orange-100 mt-2">Gestiona los pedidos y el estado de las mesas asignadas.</p>
        </div>
        <div class="bg-orange-600 text-white rounded-3xl p-5 shadow-xl text-center">
            <i class="fa-solid fa-user-tie text-4xl mb-2"></i>
            <p class="font-black">Mesero</p>
            <p class="text-xs text-orange-100">Servicio activo</p>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">

    <a href="admin_mesas.php"
       class="group bg-white rounded-2xl shadow p-6 border border-orange-100 hover:border-orange-400 hover:-translate-y-1 transition flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-2xl shadow-lg">
            <i class="fa-solid fa-chair"></i>
        </div>
        <div>
            <p class="text-lg font-black text-stone-900">Ver Mesas</p>
            <p class="text-sm text-stone-400">Consulta y actualiza el estado de las mesas</p>
        </div>
        <i class="fa-solid fa-arrow-right ml-auto text-orange-400 group-hover:translate-x-1 transition"></i>
    </a>

    <a href="admin_pedidos.php"
       class="group bg-white rounded-2xl shadow p-6 border border-orange-100 hover:border-orange-400 hover:-translate-y-1 transition flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-2xl shadow-lg">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
            <p class="text-lg font-black text-stone-900">Pedidos</p>
            <p class="text-sm text-stone-400">Revisa y gestiona los pedidos activos</p>
        </div>
        <i class="fa-solid fa-arrow-right ml-auto text-orange-400 group-hover:translate-x-1 transition"></i>
    </a>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
