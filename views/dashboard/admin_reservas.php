<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') {
    header('Location: ../usuarios/login.php'); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reserva.php';

$db       = (new Database())->conectar();
$reservas = (new Reserva($db))->obtenerTodos();
$titulo   = 'Reservas';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="bg-white rounded-2xl shadow p-6">

    <div class="flex items-center gap-3 mb-6">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-xl shadow">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
            <h2 class="text-2xl font-black text-stone-900">Reservas</h2>
            <p class="text-sm text-stone-400"><?= count($reservas) ?> reserva(s) registrada(s)</p>
        </div>
    </div>

    <?php if (empty($reservas)): ?>
        <div class="text-center py-12 text-stone-400">
            <i class="fa-solid fa-calendar-xmark text-5xl mb-3 block text-stone-300"></i>
            <p class="text-lg font-bold">No hay reservas registradas</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-orange-50 text-stone-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="p-4 rounded-tl-xl">#</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Hora</th>
                        <th class="p-4">Mesa</th>
                        <th class="p-4 text-center">Personas</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4 rounded-tr-xl text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r):
                        $estClass = match($r['nombre_estado'] ?? '') {
                            'Confirmada' => 'bg-green-100 text-green-700',
                            'Cancelada'  => 'bg-red-100 text-red-700',
                            default      => 'bg-amber-100 text-amber-700',
                        };
                    ?>
                        <tr class="border-t hover:bg-orange-50 transition">
                            <td class="p-4 font-mono text-stone-400"><?= (int)$r['id_reserva'] ?></td>
                            <td class="p-4"><?= htmlspecialchars(isset($r['fecha_reserva']) ? date('d/m/Y', strtotime($r['fecha_reserva'])) : '—') ?></td>
                            <td class="p-4"><?= htmlspecialchars(substr($r['hora_reserva'] ?? '—', 0, 5)) ?></td>
                            <td class="p-4 font-bold text-orange-600">Mesa #<?= htmlspecialchars($r['numero_mesa'] ?? '?') ?></td>
                            <td class="p-4 text-center"><?= (int)($r['numero_personas'] ?? 0) ?></td>
                            <td class="p-4 font-semibold"><?= htmlspecialchars($r['id_cliente'] ?? '—') ?></td>
                            <td class="p-4 text-center">
                                <span class="text-xs font-bold px-3 py-1 rounded-full <?= $estClass ?>">
                                    <?= htmlspecialchars($r['nombre_estado'] ?? 'Pendiente') ?>
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
