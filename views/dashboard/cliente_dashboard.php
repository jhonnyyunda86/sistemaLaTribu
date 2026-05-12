<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'cliente') {
    header('Location: ../usuarios/login.php'); exit;
}
$titulo = 'Panel Cliente';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$nombre = $_SESSION['usuario']['nombre'] ?? 'Cliente';
?>

<div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:28px;padding:2rem;" class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <p class="text-orange-300 font-bold uppercase tracking-wide text-sm mb-2">Bienvenido a La Tribu</p>
            <h1 class="text-4xl font-black text-white">Hola, <?= htmlspecialchars($nombre) ?> 👋</h1>
            <p class="text-orange-100 mt-2">Consulta el menú, realiza reservas y disfruta la experiencia.</p>
        </div>
        <div class="bg-orange-600 text-white rounded-3xl p-5 shadow-xl text-center">
            <i class="fa-solid fa-utensils text-4xl mb-2"></i>
            <p class="font-black">La Tribu</p>
            <p class="text-xs text-orange-100">Restaurante</p>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">

    <a href="cliente_reservas.php"
       class="group bg-white rounded-2xl shadow p-6 border border-orange-100 hover:border-orange-400 hover:-translate-y-1 transition flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-2xl shadow-lg">
            <i class="fa-solid fa-calendar-plus"></i>
        </div>
        <div>
            <p class="text-lg font-black text-stone-900">Hacer una Reserva</p>
            <p class="text-sm text-stone-400">Reserva tu mesa para la fecha que prefieras</p>
        </div>
        <i class="fa-solid fa-arrow-right ml-auto text-orange-400 group-hover:translate-x-1 transition"></i>
    </a>

    <div class="bg-white rounded-2xl shadow p-6 border border-orange-100 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-stone-600 to-stone-500 text-white flex items-center justify-center text-2xl shadow-lg">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div>
            <p class="text-lg font-black text-stone-900">Horario</p>
            <p class="text-sm text-stone-400">Almuerzo: 12:00 – 15:00</p>
            <p class="text-sm text-stone-400">Cena: 18:00 – 22:00</p>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
