<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';

$db      = (new Database())->conectar();
$pedidos = (new Pedido($db))->obtenerTodos();
$titulo  = 'Pedidos';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="bg-white rounded-2xl shadow p-6">

    <div class="flex items-center gap-3 mb-6">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-xl shadow">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
            <h2 class="text-2xl font-black text-stone-900">Pedidos</h2>
            <p class="text-sm text-stone-400"><?= count($pedidos) ?> pedido(s) registrado(s)</p>
        </div>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="text-center py-12 text-stone-400">
            <i class="fa-solid fa-receipt text-5xl mb-3 block text-stone-300"></i>
            <p class="text-lg font-bold">No hay pedidos registrados</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-orange-50 text-stone-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="p-4 rounded-tl-xl">#</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                        <tr class="border-t hover:bg-orange-50 transition">
                            <td class="p-4 font-mono text-stone-400"><?= (int)$p['id_pedido'] ?></td>
                            <td class="p-4"><?= htmlspecialchars($p['fecha_pedido'] ?? '—') ?></td>
                            <td class="p-4 font-semibold"><?= htmlspecialchars($p['id_cliente'] ?? '—') ?></td>
                            <td class="p-4 text-center">
                                <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-700">
                                    <?= htmlspecialchars($p['id_estado_pedido'] ?? 'Pendiente') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
