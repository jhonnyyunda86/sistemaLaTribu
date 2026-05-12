<!DOCTYPE html>
<html lang="es" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurante La Tribu</title>

    <link rel="shortcut icon" type="image/png" href="public/img/ico.png">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        tribu: {
                            cream: '#FFF7ED',
                            orange: '#EA580C',
                            dark: '#1C1917',
                            brown: '#7C2D12',
                            gold: '#F59E0B'
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .glass-nav {
            background: rgba(255, 247, 237, 0.82);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(234, 88, 12, 0.15);
        }

        .text-gradient {
            background: linear-gradient(135deg, #EA580C, #F59E0B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-bg {
            background:
                linear-gradient(rgba(28, 25, 23, .72), rgba(28, 25, 23, .78)),
                url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body class="bg-orange-50 font-sans text-stone-800 overflow-x-hidden">

    <!-- NAVBAR -->
    <nav class="glass-nav fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex justify-between h-20 items-center">

                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center shadow-lg">
                        <i class="fas fa-utensils text-xl"></i>
                    </div>
                    <span class="font-black text-2xl text-stone-900">
                        La <span class="text-orange-600">Tribu</span>
                    </span>
                </div>

                <div class="hidden md:flex items-center gap-8 font-semibold text-stone-700">
                    <a href="#inicio" class="hover:text-orange-600">Inicio</a>
                    <a href="#servicios" class="hover:text-orange-600">Servicios</a>
                    <a href="#menu" class="hover:text-orange-600">Menú</a>
                    <a href="#nosotros" class="hover:text-orange-600">Nosotros</a>

                    <a href="views/usuarios/login.php"
                       class="px-5 py-2.5 rounded-full border border-orange-600 text-orange-700 hover:bg-orange-100 transition">
                        Ingresar
                    </a>

                    <a href="views/usuarios/registre.php"
                       class="px-6 py-2.5 rounded-full bg-orange-600 text-white hover:bg-orange-700 shadow-lg transition">
                        Registrarse
                    </a>
                </div>

                <button onclick="toggleMenu()" class="md:hidden text-2xl text-orange-700">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden bg-orange-50 px-6 pb-5">
            <a href="#inicio" class="block py-2">Inicio</a>
            <a href="#servicios" class="block py-2">Servicios</a>
            <a href="#menu" class="block py-2">Menú</a>
            <a href="#nosotros" class="block py-2">Nosotros</a>

            <a href="views/usuarios/login.php" class="block text-center mt-3 border border-orange-600 text-orange-700 py-2 rounded-xl">
                Ingresar
            </a>

            <a href="views/usuarios/registre.php" class="block text-center mt-3 bg-orange-600 text-white py-2 rounded-xl">
                Registrarse
            </a>
        </div>
    </nav>

    <!-- HERO -->
    <section id="inicio" class="hero-bg min-h-screen flex items-center pt-24">
        <div class="max-w-7xl mx-auto px-5 grid lg:grid-cols-2 gap-12 items-center">

            <div data-aos="fade-right">
                <span class="inline-block px-4 py-2 rounded-full bg-orange-500/20 text-orange-200 border border-orange-300/30 font-bold mb-6">
                    Sistema de gestión gastronómica
                </span>

                <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-6">
                    Bienvenido a <br>
                    <span class="text-gradient">Restaurante La Tribu</span>
                </h1>

                <p class="text-lg md:text-xl text-orange-100 max-w-xl mb-9">
                    Una plataforma moderna para administrar pedidos, reservas, usuarios,
                    inventario y procesos internos de tu restaurante de forma rápida y elegante.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="views/usuarios/login.php"
                       class="px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white rounded-full font-bold shadow-xl text-center">
                        Ingresar al sistema
                    </a>

                    <a href="views/usuarios/registre.php"
                       class="px-8 py-4 bg-white hover:bg-orange-100 text-orange-700 rounded-full font-bold shadow-xl text-center">
                        Crear cuenta
                    </a>
                </div>
            </div>

            <div data-aos="fade-left" class="relative">
                <div class="bg-white/10 backdrop-blur-xl rounded-[2rem] p-4 border border-white/20 shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80"
                         class="rounded-[1.5rem] h-[430px] w-full object-cover">
                </div>

                <div class="absolute -bottom-8 -left-6 bg-white p-5 rounded-2xl shadow-2xl">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div>
                            <p class="text-sm text-stone-500">Especialidad</p>
                            <p class="font-black text-stone-900">Sabor Tribal</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- STATS -->
    <section class="bg-white py-12 border-y border-orange-100">
        <div class="max-w-7xl mx-auto px-5 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <h3 class="text-4xl font-black text-orange-600">100%</h3>
                <p class="text-sm text-stone-500 font-semibold">Gestión organizada</p>
            </div>
            <div>
                <h3 class="text-4xl font-black text-orange-600">24/7</h3>
                <p class="text-sm text-stone-500 font-semibold">Acceso al sistema</p>
            </div>
            <div>
                <h3 class="text-4xl font-black text-orange-600">+50</h3>
                <p class="text-sm text-stone-500 font-semibold">Productos controlados</p>
            </div>
            <div>
                <h3 class="text-4xl font-black text-orange-600">Fast</h3>
                <p class="text-sm text-stone-500 font-semibold">Pedidos más rápidos</p>
            </div>
        </div>
    </section>

    <!-- SERVICIOS -->
    <section id="servicios" class="py-24 px-5">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
                <h2 class="text-orange-600 font-black uppercase mb-3">Servicios del sistema</h2>
                <h3 class="text-4xl md:text-5xl font-black text-stone-900 mb-5">
                    Todo tu restaurante en una sola plataforma
                </h3>
                <p class="text-stone-600 text-lg">
                    Controla las operaciones principales de La Tribu desde una interfaz clara, rápida y profesional.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-orange-100 hover:shadow-xl hover:-translate-y-2 transition">
                    <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl mb-6">
                        <i class="fas fa-burger"></i>
                    </div>
                    <h4 class="text-xl font-black mb-3">Gestión de pedidos</h4>
                    <p class="text-stone-600">Administra pedidos, estados, productos y atención al cliente de manera eficiente.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 shadow-sm border border-orange-100 hover:shadow-xl hover:-translate-y-2 transition">
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mb-6">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <h4 class="text-xl font-black mb-3">Control de inventario</h4>
                    <p class="text-stone-600">Consulta existencias, productos disponibles y evita pérdidas en tu restaurante.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 shadow-sm border border-orange-100 hover:shadow-xl hover:-translate-y-2 transition">
                    <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl mb-6">
                        <i class="fas fa-users-gear"></i>
                    </div>
                    <h4 class="text-xl font-black mb-3">Usuarios y roles</h4>
                    <p class="text-stone-600">Permite el acceso seguro para administradores, empleados y clientes registrados.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- MENU -->
    <section id="menu" class="py-24 bg-white px-5">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-orange-600 font-black uppercase mb-3">Especialidades</h2>
                <h3 class="text-4xl md:text-5xl font-black text-stone-900">
                    Sabores de La Tribu
                </h3>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="rounded-3xl overflow-hidden shadow-lg bg-orange-50">
                    <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=80"
                         class="h-64 w-full object-cover">
                    <div class="p-7">
                        <h4 class="text-2xl font-black mb-2">Hamburguesa Tribal</h4>
                        <p class="text-stone-600">Carne jugosa, vegetales frescos y salsa especial de la casa.</p>
                    </div>
                </div>

                <div class="rounded-3xl overflow-hidden shadow-lg bg-orange-50">
                    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=900&q=80"
                         class="h-64 w-full object-cover">
                    <div class="p-7">
                        <h4 class="text-2xl font-black mb-2">Pizza Artesanal</h4>
                        <p class="text-stone-600">Masa crocante, queso fundido e ingredientes seleccionados.</p>
                    </div>
                </div>

                <div class="rounded-3xl overflow-hidden shadow-lg bg-orange-50">
                    <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=900&q=80"
                         class="h-64 w-full object-cover">
                    <div class="p-7">
                        <h4 class="text-2xl font-black mb-2">Plato Especial</h4>
                        <p class="text-stone-600">Una combinación deliciosa para compartir en familia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- NOSOTROS -->
    <section id="nosotros" class="py-24 px-5 bg-orange-50">
        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-14 items-center">
            <div data-aos="fade-right">
                <img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=1200&q=80"
                     class="rounded-[2rem] shadow-2xl h-[500px] w-full object-cover">
            </div>

            <div data-aos="fade-left">
                <span class="text-orange-600 font-black uppercase">Sobre nosotros</span>
                <h2 class="text-4xl md:text-5xl font-black text-stone-900 mt-3 mb-6">
                    Una experiencia creada para compartir
                </h2>
                <p class="text-lg text-stone-600 leading-relaxed mb-8">
                    Restaurante La Tribu combina sabor, atención y tecnología. Este sistema está diseñado
                    para mejorar la administración interna, facilitar el registro de usuarios y permitir
                    una gestión más clara de los procesos del restaurante.
                </p>

                <div class="space-y-5">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-orange-600 text-white rounded-xl flex items-center justify-center">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-lg">Procesos más rápidos</h4>
                            <p class="text-stone-600">Menos desorden y más control operativo.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-orange-600 text-white rounded-xl flex items-center justify-center">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-lg">Acceso seguro</h4>
                            <p class="text-stone-600">Ingreso mediante login y registro de usuarios.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-24 bg-stone-950 text-white text-center px-5 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>

        <div class="relative z-10 max-w-4xl mx-auto">
            <h2 class="text-4xl md:text-5xl font-black mb-6">
                ¿Listo para unirte a La Tribu?
            </h2>
            <p class="text-orange-100 text-lg mb-10">
                Regístrate y comienza a usar el sistema del restaurante.
            </p>

            <a href="views/usuarios/registre.php"
               class="inline-flex items-center px-9 py-4 bg-orange-600 hover:bg-orange-700 rounded-full font-black shadow-xl">
                Registrarme ahora
                <i class="fas fa-arrow-right ml-3"></i>
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-stone-950 text-stone-400 border-t border-stone-800 py-14 px-5">
        <div class="max-w-7xl mx-auto grid md:grid-cols-4 gap-10">

            <div class="md:col-span-2">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <span class="text-white text-2xl font-black">La Tribu</span>
                </div>
                <p class="max-w-md">
                    Sistema de gestión para restaurante orientado a mejorar pedidos,
                    usuarios, productos e inventario.
                </p>
            </div>

            <div>
                <h4 class="text-white font-black mb-5">Enlaces</h4>
                <ul class="space-y-3">
                    <li><a href="#inicio" class="hover:text-orange-400">Inicio</a></li>
                    <li><a href="#servicios" class="hover:text-orange-400">Servicios</a></li>
                    <li><a href="#menu" class="hover:text-orange-400">Menú</a></li>
                    <li><a href="#nosotros" class="hover:text-orange-400">Nosotros</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-black mb-5">Acceso</h4>
                <ul class="space-y-3">
                    <li><a href="views/usuarios/login.php" class="hover:text-orange-400">Ingresar</a></li>
                    <li><a href="views/usuarios/registre.php" class="hover:text-orange-400">Registrarse</a></li>
                </ul>
            </div>
        </div>

        <div class="max-w-7xl mx-auto border-t border-stone-800 mt-10 pt-6 text-center text-sm">
            © 2026 Restaurante La Tribu. Todos los derechos reservados.
        </div>
    </footer>

    <!-- CHATBOT VISUAL -->
    <div id="chatbot-container" class="fixed bottom-6 right-6 z-50">
        <div id="chatbot-window"
             class="hidden bg-white w-80 rounded-3xl shadow-2xl border border-orange-100 overflow-hidden mb-4">
            <div class="bg-gradient-to-r from-orange-600 to-amber-500 p-4 text-white flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h4 class="font-black">Asistente La Tribu</h4>
                        <p class="text-xs">En línea</p>
                    </div>
                </div>
                <button onclick="toggleChatbot()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-4 h-72 overflow-y-auto bg-orange-50">
                <div class="bg-white p-3 rounded-2xl shadow-sm text-sm text-stone-700">
                    ¡Hola! Soy el asistente de Restaurante La Tribu. Puedo orientarte sobre registro, ingreso y uso del sistema.
                </div>
            </div>

            <div class="p-4 border-t flex gap-2">
                <input type="text" placeholder="Escribe tu mensaje..."
                       class="w-full border rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <button class="w-10 h-10 rounded-full bg-orange-600 text-white">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

        <button onclick="toggleChatbot()"
                class="w-16 h-16 bg-orange-600 hover:bg-orange-700 text-white rounded-full shadow-2xl text-2xl">
            <i class="fas fa-comment-dots"></i>
        </button>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        AOS.init({
            once: true,
            duration: 800,
            offset: 50
        });

        function toggleMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        function toggleChatbot() {
            document.getElementById('chatbot-window').classList.toggle('hidden');
        }
    </script>

</body>
</html>