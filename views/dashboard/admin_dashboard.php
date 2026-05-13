<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'admin') { 
    header('Location: ../usuarios/login.php'); 
    exit; 
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioAdmin.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../../models/Mesa.php';
require_once __DIR__ . '/../../models/Reserva.php';
require_once __DIR__ . '/../../models/Pedido.php';

$db = (new Database())->conectar();

$usuarioAdmin  = new UsuarioAdmin($db); // ← nombre correcto
$productoModel = new Producto($db);
$mesaModel     = new Mesa($db);
$reservaModel  = new Reserva($db);
$pedidoModel   = new Pedido($db);

// ── PROCESAR FORMULARIO ──────────────────────────────────────────────────────
$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_usuario') {

    $nombre   = trim($_POST['nombre']   ?? '');
    $correo   = trim($_POST['correo']   ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = trim($_POST['rol']      ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (!$nombre || !$correo || !$telefono || !$rol || !$password) {
        $mensajeError = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensajeError = 'El correo electrónico no es válido.';
    } elseif ($password !== $confirm) {
        $mensajeError = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $mensajeError = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($usuarioAdmin->existeCorreo($correo)) {
        $mensajeError = 'Este correo ya está registrado.';
    } else {
        $creado = $usuarioAdmin->crear([  // ← $usuarioAdmin
            'nombre'   => $nombre,
            'correo'   => $correo,
            'telefono' => $telefono,
            'role'     => $rol,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if ($creado) {
            $mensajeExito = "Usuario «{$nombre}» creado correctamente.";
            $_POST = [];
        } else {
            $mensajeError = 'No se pudo crear el usuario. El correo ya puede estar registrado.';
        }
    }
}
// ────────────────────────────────────────────────────────────────────────────

$cards = [
    ['Clientes',  $usuarioAdmin->contarPorRol('cliente'), 'fa-users'],   // ← $usuarioAdmin
    ['Meseros',   $usuarioAdmin->contarPorRol('mesero'),  'fa-user-tie'],// ← $usuarioAdmin
    ['Productos', $productoModel->contar(),               'fa-utensils'],
    ['Mesas',     $mesaModel->contar(),                   'fa-chair'],
    ['Reservas',  $reservaModel->contar(),                'fa-calendar-check'],
    ['Pedidos',   $pedidoModel->contar(),                 'fa-receipt'],
];

$titulo = 'Dashboard Administrador';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .dashboard-bg {
        padding: 0;
        border-radius: 0;
    }
    .glass-card {
        background: rgba(255, 247, 237, 0.92);
        backdrop-filter: blur(14px);
        border: 1px solid rgba(251, 146, 60, 0.25);
    }
    .dark-glass {
        background: rgba(28, 25, 23, 0.78);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(251, 146, 60, 0.25);
    }

    /* ── MODAL ── */
    #modalUsuario {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0,0,0,.6);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
    }
    #modalUsuario.activo { display: flex; }

    .modal-box {
        background: #fff;
        border-radius: 28px;
        width: 100%;
        max-width: 520px;
        padding: 2.5rem;
        box-shadow: 0 30px 80px rgba(0,0,0,.35);
        position: relative;
        animation: popIn .25s ease;
    }
    @keyframes popIn {
        from { transform: scale(.92); opacity: 0; }
        to   { transform: scale(1);   opacity: 1; }
    }

    .modal-input {
        width: 100%;
        border: 1.5px solid #e5e0d8;
        border-radius: 14px;
        padding: .7rem 1rem;
        font-size: .95rem;
        outline: none;
        transition: border-color .2s;
        background: #fafaf9;
    }
    .modal-input:focus { border-color: #ea580c; background: #fff; }
    .modal-label {
        display: block;
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #78716c;
        margin-bottom: .35rem;
    }
</style>

<!-- ══════════════════════════════════════════════════════════════════════
     MODAL AGREGAR USUARIO
     ══════════════════════════════════════════════════════════════════════ -->
<div id="modalUsuario" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
    <div class="modal-box">

        <!-- Cerrar -->
        <button onclick="cerrarModal()"
                class="absolute top-4 right-4 w-9 h-9 rounded-full bg-stone-100 hover:bg-orange-100 text-stone-500 hover:text-orange-600 transition flex items-center justify-center">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Título -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-lg shadow">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
                <h2 id="modalTitulo" class="text-2xl font-black text-stone-900">Agregar Usuario</h2>
                <p class="text-sm text-stone-400">Completa todos los campos</p>
            </div>
        </div>

        <!-- Alertas -->
        <?php if ($mensajeError): ?>
            <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2 text-sm font-semibold">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($mensajeError) ?>
            </div>
        <?php endif; ?>
        <?php if ($mensajeExito): ?>
            <div class="mb-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2 text-sm font-semibold">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensajeExito) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="" novalidate>
            <input type="hidden" name="accion" value="crear_usuario">

            <div class="grid grid-cols-1 gap-5">

                <!-- Nombre completo -->
                <div>
                    <label class="modal-label" for="f_nombre">
                        <i class="fa-solid fa-user mr-1"></i> Nombre completo
                    </label>
                    <input id="f_nombre" name="nombre" type="text" class="modal-input"
                           placeholder="Ej. María García López" required
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <!-- Correo -->
                <div>
                    <label class="modal-label" for="f_correo">
                        <i class="fa-solid fa-envelope mr-1"></i> Correo electrónico
                    </label>
                    <input id="f_correo" name="correo" type="email" class="modal-input"
                           placeholder="correo@ejemplo.com" required
                           value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="modal-label" for="f_telefono">
                        <i class="fa-solid fa-phone mr-1"></i> Teléfono
                    </label>
                    <input id="f_telefono" name="telefono" type="tel" class="modal-input"
                           placeholder="Ej. 3001234567" required
                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                </div>

                <!-- Rol -->
                <div>
                    <label class="modal-label" for="f_rol">
                        <i class="fa-solid fa-id-badge mr-1"></i> Rol
                    </label>
                    <select id="f_rol" name="rol" class="modal-input" required>
                        <option value="" disabled <?= empty($_POST['rol']) ? 'selected' : '' ?>>Selecciona un rol…</option>
                        <option value="admin"   <?= (($_POST['rol'] ?? '') === 'admin')   ? 'selected' : '' ?>>Administrador</option>
                        <option value="mesero"  <?= (($_POST['rol'] ?? '') === 'mesero')  ? 'selected' : '' ?>>Mesero</option>
                        <option value="cliente" <?= (($_POST['rol'] ?? '') === 'cliente') ? 'selected' : '' ?>>Cliente</option>
                    </select>
                </div>

                <!-- Contraseña -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="modal-label" for="f_password">
                            <i class="fa-solid fa-lock mr-1"></i> Contraseña
                        </label>
                        <input id="f_password" name="password" type="password" class="modal-input"
                               placeholder="Mín. 6 caracteres" required>
                    </div>
                    <div>
                        <label class="modal-label" for="f_confirm">
                            <i class="fa-solid fa-lock mr-1"></i> Confirmar
                        </label>
                        <input id="f_confirm" name="confirm" type="password" class="modal-input"
                               placeholder="Repite la contraseña" required>
                    </div>
                </div>

            </div><!-- /grid -->

            <!-- Botones -->
            <div class="mt-7 flex gap-3">
                <button type="button" onclick="cerrarModal()"
                        class="flex-1 py-3 rounded-2xl border-2 border-stone-200 text-stone-600 font-bold hover:bg-stone-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 py-3 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 text-white font-black shadow-lg hover:opacity-90 transition">
                    <i class="fa-solid fa-user-plus mr-2"></i> Crear usuario
                </button>
            </div>

        </form>
    </div>
</div>
<!-- /MODAL -->


<div class="dashboard-bg">

    <!-- ENCABEZADO -->
    <div class="mb-8 dark-glass rounded-[28px] p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <p class="text-orange-300 font-bold uppercase tracking-wide text-sm mb-2">
                    Panel administrativo
                </p>
                <h1 class="text-4xl md:text-5xl font-black text-white">
                    Bienvenido, Administrador
                </h1>
                <p class="text-orange-100 mt-3 max-w-2xl">
                    Gestiona clientes, meseros, productos, mesas, reservas y pedidos de Restaurante La Tribu.
                </p>

                <!-- ── BOTÓN AGREGAR USUARIO ── -->
                <button onclick="abrirModal()"
                        class="mt-5 inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-400 text-white font-black px-6 py-3 rounded-2xl shadow-lg transition hover:-translate-y-0.5">
                    <i class="fa-solid fa-user-plus"></i> Agregar Usuario
                </button>
            </div>

            <div class="bg-orange-600 text-white rounded-3xl p-5 shadow-xl text-center">
                <i class="fa-solid fa-utensils text-4xl mb-2"></i>
                <p class="font-black">La Tribu</p>
                <p class="text-xs text-orange-100">Sistema de gestión</p>
            </div>
        </div>
    </div>

    <!-- Mensaje de éxito flotante (sin modal) -->
    <?php if ($mensajeExito): ?>
        <div class="mb-6 p-5 bg-green-50 border border-green-200 text-green-800 rounded-2xl flex items-center gap-3 font-semibold shadow">
            <i class="fa-solid fa-circle-check text-xl text-green-600"></i>
            <?= htmlspecialchars($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <!-- TARJETAS -->
    <div class="grid md:grid-cols-3 gap-6">
        <?php foreach($cards as $c): ?>
            <div class="glass-card rounded-[26px] shadow-xl p-6 flex items-center gap-5 hover:-translate-y-2 hover:shadow-2xl transition duration-300">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center text-2xl shadow-lg">
                    <i class="fa-solid <?= $c[2] ?>"></i>
                </div>
                <div>
                    <p class="text-stone-500 text-sm font-bold uppercase tracking-wide"><?= $c[0] ?></p>
                    <p class="text-4xl font-black text-stone-900"><?= $c[1] ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- CONTENIDO INFERIOR -->
    <div class="mt-8 grid lg:grid-cols-2 gap-6">

        <!-- ACCESOS RÁPIDOS -->
        <div class="glass-card rounded-[26px] shadow-xl p-7">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h3 class="font-black text-2xl text-stone-900">Accesos rápidos</h3>
                    <p class="text-sm text-stone-500">Administra las secciones principales.</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_menu.php">
                    <i class="fa-solid fa-utensils text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Gestionar menú</p>
                </a>
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_mesas.php">
                    <i class="fa-solid fa-chair text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Ver mesas</p>
                </a>
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_reservas.php">
                    <i class="fa-solid fa-calendar-check text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Reservas</p>
                </a>
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_pedidos.php">
                    <i class="fa-solid fa-receipt text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Pedidos</p>
                </a>
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_usuarios.php">
                    <i class="fa-solid fa-user-tie text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Usuarios</p>
                </a>
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_reportes.php">
                    <i class="fa-solid fa-chart-bar text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Reportes</p>
                </a>
                <a class="group p-5 border border-orange-100 rounded-2xl bg-white hover:bg-orange-600 hover:text-white transition shadow-sm" href="admin_inventario.php">
                    <i class="fa-solid fa-boxes-stacked text-orange-600 group-hover:text-white text-2xl mb-3"></i>
                    <p class="font-black">Inventario</p>
                </a>
            </div>
        </div>

        <!-- ESTADO DEL SISTEMA -->
        <div class="glass-card rounded-[26px] shadow-xl p-7">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div>
                    <h3 class="font-black text-2xl text-stone-900">Estado del sistema</h3>
                    <p class="text-sm text-stone-500">Información general del servidor.</p>
                </div>
            </div>
            <div class="p-5 bg-green-50 text-green-700 rounded-2xl font-black flex items-center gap-3 border border-green-100">
                <i class="fa-solid fa-circle-check text-2xl"></i>
                Base de datos conectada
            </div>
            <div class="mt-5 p-5 bg-white rounded-2xl border border-orange-100">
                <p class="text-stone-500 text-sm font-bold uppercase">Fecha del servidor</p>
                <p class="text-2xl font-black text-stone-900 mt-1"><?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="mt-5 p-5 bg-orange-50 rounded-2xl border border-orange-100">
                <p class="text-orange-700 font-bold">
                    <i class="fa-solid fa-shield-halved mr-2"></i>
                    Sesión administrativa activa
                </p>
            </div>
        </div>

    </div>
</div>

<script>
    // Abrir/cerrar modal
    function abrirModal() {
        document.getElementById('modalUsuario').classList.add('activo');
        document.body.style.overflow = 'hidden';
    }
    function cerrarModal() {
        document.getElementById('modalUsuario').classList.remove('activo');
        document.body.style.overflow = '';
    }

    // Cerrar al hacer clic fuera del cuadro
    document.getElementById('modalUsuario').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });

    // Si hubo error de validación, reabrir el modal automáticamente
    <?php if ($mensajeError): ?>
        document.addEventListener('DOMContentLoaded', abrirModal);
    <?php endif; ?>
</script>

<?php require_once __DIR__.'/../layouts/footer.php'; ?>