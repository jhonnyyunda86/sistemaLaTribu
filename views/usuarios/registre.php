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
    <title>La Tribu | Registro de clientes</title>

    <link rel="shortcut icon" type="image/png" href="../../img/logo.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .fondo-registro {
            background:
                linear-gradient(rgba(28, 25, 23, .78), rgba(28, 25, 23, .84)),
                url('https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=2000&q=80');
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

<body class="min-h-screen fondo-registro flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-5xl rounded-[34px] overflow-hidden shadow-2xl grid md:grid-cols-2 glass">

        <!-- FORMULARIO -->
        <div class="relative bg-white p-8 md:p-14 flex flex-col justify-center">

            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255, 255, 255, 0.22),transparent_35%)]"></div>

            <div class="relative z-10">

                <a href="../../index.php" class="inline-flex items-center text-orange-700 hover:text-orange-900 font-semibold text-sm mb-5">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver al inicio
                </a>

                <div class="flex justify-center mb-5">
                    <div class="bg-white rounded-3xl shadow-lg px-6 py-4 border border-orange-100">
                        <img src="../../img/logo.png" alt="La Tribu" class="w-32">
                    </div>
                </div>

                <h2 class="text-center text-3xl font-black text-stone-900 mb-2">
                    Crear Cuenta
                </h2>

                <p class="text-center text-stone-500 mb-8">
                    Regístrate como cliente de Restaurante La Tribu
                </p>

                <form action="../../controllers/UsuarioController.php" method="POST">
                    <input type="hidden" name="role" value="cliente">

                    <div class="relative mb-4">
                        <i class="fas fa-user absolute left-5 top-4 text-orange-700"></i>
                        <input type="text" name="nombre" required
                            class="w-full pl-12 pr-4 py-4 rounded-full bg-white border border-orange-200 text-stone-800 placeholder-stone-400 text-sm outline-none focus:ring-4 focus:ring-orange-200 focus:border-orange-500 shadow-sm"
                            placeholder="Nombre completo">
                    </div>

                    <div class="relative mb-4">
                        <i class="fas fa-envelope absolute left-5 top-4 text-orange-700"></i>
                        <input type="email" name="correo" required
                            class="w-full pl-12 pr-4 py-4 rounded-full bg-white border border-orange-200 text-stone-800 placeholder-stone-400 text-sm outline-none focus:ring-4 focus:ring-orange-200 focus:border-orange-500 shadow-sm"
                            placeholder="Correo electrónico">
                    </div>

                    <div class="relative mb-4">
                        <i class="fas fa-phone absolute left-5 top-4 text-orange-700"></i>
                        <input type="text" name="telefono" required
                            class="w-full pl-12 pr-4 py-4 rounded-full bg-white border border-orange-200 text-stone-800 placeholder-stone-400 text-sm outline-none focus:ring-4 focus:ring-orange-200 focus:border-orange-500 shadow-sm"
                            placeholder="Teléfono">
                    </div>

                    <div class="relative mb-5">
                        <i class="fas fa-lock absolute left-5 top-4 text-orange-700"></i>
                        <input type="password" name="password" required
                            class="w-full pl-12 pr-12 py-4 rounded-full bg-white border border-orange-200 text-stone-800 placeholder-stone-400 text-sm outline-none focus:ring-4 focus:ring-orange-200 focus:border-orange-500 shadow-sm"
                            placeholder="Contraseña">
                        <i class="fas fa-eye absolute right-5 top-4 text-orange-700"></i>
                    </div>

                    <div class="flex items-center justify-center mb-6">
                        <input type="checkbox" required class="mr-2 w-5 h-5 accent-orange-600">
                        <label class="text-xs text-stone-600 font-semibold">
                            Acepto los términos y condiciones
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-orange-600 to-amber-500 text-white font-black py-4 rounded-full shadow-xl hover:shadow-orange-500/30 hover:scale-[1.02] transition">
                        Registrarse
                        <i class="fas fa-user-plus ml-2"></i>
                    </button>

                    <p class="text-center text-sm text-stone-600 mt-6">
                        ¿Ya tienes cuenta?
                        <a href="login.php" class="font-black text-orange-700 hover:underline">
                            Inicia sesión
                        </a>
                    </p>
                </form>
            </div>
        </div>

        <!-- PANEL DERECHO -->
        <div class="relative bg-stone-950/90 text-white p-10 md:p-14 flex flex-col justify-center items-center text-center">

            <div class="w-24 h-24 rounded-full bg-orange-500/15 border border-orange-400/30 flex items-center justify-center mb-6">
                <i class="fas fa-utensils text-4xl text-orange-400"></i>
            </div>

            <h2 class="text-4xl font-black text-orange-400 mb-4">
                Bienvenido de nuevo
            </h2>

            <p class="text-stone-300 mb-8 max-w-sm">
                Si ya haces parte de La Tribu, inicia sesión para continuar con tu experiencia gastronómica.
            </p>

            <a href="login.php"
                class="bg-orange-600 hover:bg-orange-700 text-white font-bold px-12 py-3 rounded-full shadow-lg transition hover:scale-105">
                Iniciar sesión
            </a>

            <p class="text-stone-400 mt-8 text-sm">
                ¿Necesitas ayuda? Comunícate con el administrador.
            </p>
        </div>

    </div>

    <?php if ($alert): ?>
    <script>
        Swal.fire({
            icon: '<?= htmlspecialchars($alert['icon']) ?>',
            title: '<?= htmlspecialchars($alert['title']) ?>',
            text: '<?= htmlspecialchars($alert['text']) ?>',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            <?php if (!empty($alert['redirect'])): ?>
                window.location.href = '<?= htmlspecialchars($alert['redirect']) ?>';
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>