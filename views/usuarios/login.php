<?php
session_start();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Tribu | Iniciar Sesión</title>

    <link rel="shortcut icon" type="image/png" href="/sistema-restaurante/img/ico.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .fondo-login {
            background:
                linear-gradient(rgba(28, 25, 23, .78), rgba(28, 25, 23, .82)),
                url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .glass {
            background: rgba(255, 247, 237, 0.10);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>

<body class="min-h-screen fondo-login flex items-center justify-center px-4 py-10">

<div class="w-full max-w-5xl rounded-[34px] overflow-hidden shadow-2xl grid md:grid-cols-2 glass">

    <!-- PANEL IZQUIERDO -->
    <div class="relative bg-stone-950/90 text-white p-10 md:p-14 flex flex-col justify-center items-center text-center">

        <a href="../../index.php" class="absolute top-6 left-6 text-orange-300 hover:text-white transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Inicio
        </a>

        <div class="w-24 h-24 rounded-full bg-orange-500/15 border border-orange-400/30 flex items-center justify-center mb-6">
            <i class="fas fa-utensils text-4xl text-orange-400"></i>
        </div>

        <h2 class="text-4xl font-black text-orange-400 mb-4">
            Bienvenido
        </h2>

        <p class="text-stone-300 mb-8 max-w-sm">
            Accede al sistema de gestión de Restaurante La Tribu para administrar tus procesos de forma rápida y segura.
        </p>

        <a href="registre.php"
            class="bg-orange-600 hover:bg-orange-700 text-white font-bold px-12 py-3 rounded-full shadow-lg transition hover:scale-105">
            Crear cuenta
        </a>

        <p class="text-stone-400 mt-8 text-sm">
            ¿No tienes una cuenta? Regístrate gratis.
        </p>
    </div>

    <!-- FORMULARIO DERECHA -->
    <div class="relative bg-white p-8 md:p-14 flex flex-col justify-center">

        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255, 255, 255, 0.22),transparent_35%)]"></div>

        <div class="relative z-10">

            <div class="flex justify-center mb-5">
                <div class="bg-white rounded-3xl shadow-lg px-6 py-4 border border-orange-100">
                    <img src="../../img/logo.png" alt="La Tribu" class="w-32">
                </div>
            </div>

            <h2 class="text-center text-3xl font-black text-stone-900 mb-2">
                Iniciar Sesión
            </h2>

            <p class="text-center text-stone-500 mb-8">
                Ingresa tus datos para continuar
            </p>

            <form action="../../controllers/AuthController.php" method="POST" class="space-y-5">

                <!-- EMAIL -->
                <div class="relative">
                    <i class="fas fa-envelope absolute left-5 top-4 text-orange-700"></i>
                    <input
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        class="w-full pl-12 pr-4 py-4 rounded-full bg-white border border-orange-200 text-stone-800 placeholder-stone-400 outline-none focus:ring-4 focus:ring-orange-200 focus:border-orange-500 shadow-sm"
                        placeholder="Correo electrónico"
                    >
                </div>

                <!-- PASSWORD -->
                <div class="relative">
                    <i class="fas fa-lock absolute left-5 top-4 text-orange-700"></i>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full pl-12 pr-4 py-4 rounded-full bg-white border border-orange-200 text-stone-800 placeholder-stone-400 outline-none focus:ring-4 focus:ring-orange-200 focus:border-orange-500 shadow-sm"
                        placeholder="Contraseña"
                    >
                </div>

                <div class="flex justify-between items-center text-sm">
                    <label class="flex items-center text-stone-600">
                        <input type="checkbox" class="mr-2 accent-orange-600">
                        Recordar sesión
                    </label>

                    <a href="#" class="text-orange-700 font-semibold hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <!-- BOTON -->
                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-orange-600 to-amber-500 text-white font-black py-4 rounded-full shadow-xl hover:shadow-orange-500/30 hover:scale-[1.02] transition"
                >
                    Iniciar Sesión
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>

                <!-- REGISTRO -->
                <p class="text-center text-sm text-stone-600">
                    ¿No tienes cuenta?
                    <a href="registre.php" class="font-black text-orange-700 hover:underline">
                        Regístrate
                    </a>
                </p>

            </form>
        </div>
    </div>

</div>

<?php if ($alert): ?>
<script>
Swal.fire({
    icon: '<?= htmlspecialchars($alert['icon']) ?>',
    title: '<?= htmlspecialchars($alert['title']) ?>',
    text: '<?= htmlspecialchars($alert['text']) ?>',
    confirmButtonText: 'Aceptar'
});
</script>
<?php endif; ?>

</body>
</html>