<!DOCTYPE html>
<?php
// Si ya hay sesión activa, redirigir al dashboard correspondiente
session_start();
if (isset($_SESSION['usuario'])) {
    $destinos = [
        'admin'   => 'views/dashboard/admin_dashboard.php',
        'mesero'  => 'views/dashboard/mesero_dashboard.php',
        'cliente' => 'views/dashboard/cliente_dashboard.php',
    ];
    $rol = $_SESSION['usuario']['role'] ?? '';
    if (isset($destinos[$rol])) {
        header('Location: ' . $destinos[$rol]);
        exit;
    }
}
?>
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

    <!-- ══════════════════════════════════════════════════════
         CHATBOT TRIBU — Asistente Virtual Inteligente
    ══════════════════════════════════════════════════════ -->

    <!-- Burbuja flotante -->
    <div id="chat-fab" onclick="toggleChat()"
         style="position:fixed;bottom:1.75rem;right:1.75rem;z-index:9999;
                width:62px;height:62px;border-radius:50%;
                background:linear-gradient(135deg,#ea580c,#f59e0b);
                color:#fff;border:none;cursor:pointer;
                box-shadow:0 8px 28px rgba(234,88,12,.5);
                display:flex;align-items:center;justify-content:center;
                font-size:1.5rem;transition:transform .2s;"
         onmouseover="this.style.transform='scale(1.1)'"
         onmouseout="this.style.transform='scale(1)'">
        <i class="fas fa-comment-dots" id="chat-fab-icon"></i>
        <span id="chat-notif"
              style="position:absolute;top:-4px;right:-4px;
                     background:#dc2626;color:#fff;font-size:.6rem;font-weight:900;
                     width:18px;height:18px;border-radius:50%;
                     display:none;align-items:center;justify-content:center;
                     border:2px solid #fff;">1</span>
    </div>

    <!-- Ventana del chat -->
    <div id="chat-window"
         style="display:none;position:fixed;bottom:6rem;right:1.75rem;z-index:9998;
                width:370px;max-width:calc(100vw - 2rem);
                background:#fff;border-radius:24px;
                box-shadow:0 24px 64px rgba(0,0,0,.18);
                border:1px solid rgba(234,88,12,.15);
                overflow:hidden;
                flex-direction:column;">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,#ea580c,#f59e0b);padding:1rem 1.25rem;
                    display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:42px;height:42px;border-radius:50%;
                            background:rgba(255,255,255,.2);
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.2rem;color:#fff;position:relative;">
                    <i class="fas fa-robot"></i>
                    <span style="position:absolute;bottom:1px;right:1px;
                                 width:10px;height:10px;background:#22c55e;
                                 border-radius:50%;border:2px solid #fff;"></span>
                </div>
                <div>
                    <p style="font-weight:900;color:#fff;font-size:.95rem;">Tribu Assistant</p>
                    <p style="font-size:.7rem;color:rgba(255,255,255,.8);">
                        <span id="chat-typing-indicator" style="display:none;">escribiendo...</span>
                        <span id="chat-online">En línea · siempre disponible</span>
                    </p>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;">
                <button onclick="limpiarChat()" title="Limpiar chat"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;
                           width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:.8rem;"
                    onmouseover="this.style.background='rgba(255,255,255,.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-trash-can"></i>
                </button>
                <button onclick="toggleChat()"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;
                           width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:.9rem;"
                    onmouseover="this.style.background='rgba(255,255,255,.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Mensajes -->
        <div id="chat-messages"
             style="flex:1;overflow-y:auto;padding:1rem;
                    background:#fafaf9;min-height:320px;max-height:380px;
                    display:flex;flex-direction:column;gap:.75rem;">
        </div>

        <!-- Respuestas rápidas -->
        <div id="chat-quick"
             style="padding:.6rem 1rem;display:flex;gap:.4rem;flex-wrap:wrap;
                    border-top:1px solid #f5f0eb;background:#fff;">
        </div>

        <!-- Input -->
        <div style="padding:.75rem 1rem;border-top:1px solid #f5f0eb;background:#fff;
                    display:flex;gap:.5rem;align-items:center;">
            <input id="chat-input" type="text" placeholder="Escribe tu mensaje..."
                   onkeydown="if(event.key==='Enter') enviarMensaje()"
                   style="flex:1;padding:.6rem 1rem;border:2px solid #e7e5e4;
                          border-radius:999px;font-size:.85rem;outline:none;
                          transition:border-color .2s;"
                   onfocus="this.style.borderColor='#ea580c'"
                   onblur="this.style.borderColor='#e7e5e4'">
            <button onclick="enviarMensaje()"
                    style="width:38px;height:38px;border-radius:50%;border:none;
                           background:linear-gradient(135deg,#ea580c,#f59e0b);
                           color:#fff;cursor:pointer;font-size:.9rem;flex-shrink:0;
                           transition:transform .15s;"
                    onmouseover="this.style.transform='scale(1.1)'"
                    onmouseout="this.style.transform='scale(1)'">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <style>
    @keyframes chatIn { from{opacity:0;transform:translateY(20px) scale(.95)} to{opacity:1;transform:translateY(0) scale(1)} }
    @keyframes msgIn  { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .msg-bot  { align-self:flex-start;max-width:82%; }
    .msg-user { align-self:flex-end;max-width:82%; }
    .bubble-bot  { background:#fff;border:1px solid #f5f0eb;color:#1c1917;border-radius:18px 18px 18px 4px;padding:.65rem .9rem;font-size:.85rem;line-height:1.5;box-shadow:0 2px 8px rgba(0,0,0,.06);animation:msgIn .25s ease; }
    .bubble-user { background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;border-radius:18px 18px 4px 18px;padding:.65rem .9rem;font-size:.85rem;line-height:1.5;animation:msgIn .25s ease; }
    .quick-btn { padding:.35rem .85rem;border-radius:999px;border:1.5px solid #ea580c;color:#ea580c;background:#fff;font-size:.75rem;font-weight:700;cursor:pointer;transition:all .2s;white-space:nowrap; }
    .quick-btn:hover { background:#ea580c;color:#fff; }
    #chat-messages::-webkit-scrollbar { width:4px; }
    #chat-messages::-webkit-scrollbar-thumb { background:#fdba74;border-radius:999px; }
    </style>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, duration: 800, offset: 50 });
        function toggleMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        /* ══════════════════════════════════════════════════════
           CHATBOT TRIBU — Motor de conversación
        ══════════════════════════════════════════════════════ */
        var chatAbierto   = false;
        var chatHistorial = [];

        var KB = {
            productos: [
                { nombre:'Hamburguesa Tribu',  precio:'$22.000', cat:'Comidas rápidas', emoji:'🍔' },
                { nombre:'Salchipapa Especial',precio:'$18.000', cat:'Comidas rápidas', emoji:'🍟' },
                { nombre:'Limonada Natural',   precio:'$7.000',  cat:'Bebidas',         emoji:'🍋' },
                { nombre:'Costillas BBQ',      precio:'$32.000', cat:'Parrilla',        emoji:'🥩' },
            ],
            horarios:   'Almuerzo: 12:00 – 15:00 · Cena: 18:00 – 22:00',
            beneficios: ['📦 Historial de pedidos guardado','⚡ Reservas más rápidas','🎁 Acceso a promociones exclusivas','📍 Seguimiento de tus pedidos','✨ Experiencia personalizada'],
        };

        var QUICK = {
            inicio:  ['🍔 Ver menú','📅 Reservar mesa','🛒 Hacer pedido','🕐 Horarios','❓ Ayuda'],
            menu:    ['🍔 Hamburguesas','🥩 Parrilla','🍹 Bebidas','💰 Precios','🔙 Volver'],
            pedido:  ['🛒 Pedir ahora','📋 Ver menú primero','🔙 Volver'],
            auth:    ['🔑 Iniciar sesión','📝 Registrarme','🔙 Volver'],
            ayuda:   ['🍔 Ver menú','📅 Reservar','🛒 Pedir','📞 Contacto','🔙 Volver'],
        };

        function toggleChat() {
            chatAbierto = !chatAbierto;
            var win  = document.getElementById('chat-window');
            var icon = document.getElementById('chat-fab-icon');
            win.style.display = chatAbierto ? 'flex' : 'none';
            if (chatAbierto) win.style.animation = 'chatIn .3s ease';
            icon.className = chatAbierto ? 'fas fa-times' : 'fas fa-comment-dots';
            document.getElementById('chat-notif').style.display = 'none';
            if (chatAbierto && chatHistorial.length === 0) setTimeout(mensajeBienvenida, 300);
        }

        function limpiarChat() {
            chatHistorial = [];
            document.getElementById('chat-messages').innerHTML = '';
            document.getElementById('chat-quick').innerHTML    = '';
            setTimeout(mensajeBienvenida, 200);
        }

        function addMsg(texto, tipo, delay) {
            delay = delay || 0;
            setTimeout(function() {
                var box    = document.getElementById('chat-messages');
                var wrap   = document.createElement('div');
                wrap.className = 'msg-' + tipo;
                var bubble = document.createElement('div');
                bubble.className = 'bubble-' + tipo;
                bubble.innerHTML = texto;
                wrap.appendChild(bubble);
                box.appendChild(wrap);
                box.scrollTop = box.scrollHeight;
                chatHistorial.push({ tipo: tipo, texto: texto });
            }, delay);
        }

        function mostrarTyping(cb, delay) {
            delay = delay || 900;
            var ind = document.getElementById('chat-typing-indicator');
            var onl = document.getElementById('chat-online');
            ind.style.display = 'inline'; onl.style.display = 'none';
            setTimeout(function() { ind.style.display = 'none'; onl.style.display = 'inline'; cb(); }, delay);
        }

        function setQuick(key) {
            var c = document.getElementById('chat-quick');
            c.innerHTML = '';
            (QUICK[key] || []).forEach(function(label) {
                var btn = document.createElement('button');
                btn.className   = 'quick-btn';
                btn.textContent = label;
                btn.onclick     = function() { procesarQuick(label); };
                c.appendChild(btn);
            });
        }

        function mensajeBienvenida() {
            addMsg('👋 ¡Hola! Soy <strong>Tribu Assistant</strong>, tu guía en Restaurante La Tribu.', 'bot', 0);
            addMsg('Puedo ayudarte con el <strong>menú</strong>, <strong>reservas</strong>, <strong>pedidos</strong> y más. ¿Qué necesitas?', 'bot', 700);
            setTimeout(function() { setQuick('inicio'); }, 1000);
        }

        function procesarQuick(label) {
            addMsg(label, 'user');
            var t = label.toLowerCase();
            if (t.includes('menú')||t.includes('menu')||t.includes('hamburguesa')||t.includes('parrilla')||t.includes('bebida')) {
                mostrarTyping(function(){ respuestaMenu(t); });
            } else if (t.includes('reservar')||t.includes('mesa')) {
                mostrarTyping(respuestaReserva);
            } else if (t.includes('pedir')||t.includes('pedido')) {
                mostrarTyping(respuestaPedido);
            } else if (t.includes('horario')||t.includes('🕐')) {
                mostrarTyping(respuestaHorario);
            } else if (t.includes('precio')) {
                mostrarTyping(respuestaPrecios);
            } else if (t.includes('iniciar sesión')||t.includes('🔑')) {
                window.location.href = 'views/usuarios/login.php';
            } else if (t.includes('registrar')||t.includes('📝')) {
                window.location.href = 'views/usuarios/registre.php';
            } else if (t.includes('contacto')) {
                mostrarTyping(respuestaContacto);
            } else if (t.includes('ayuda')||t.includes('❓')) {
                mostrarTyping(respuestaAyuda);
            } else if (t.includes('volver')||t.includes('🔙')) {
                mostrarTyping(function(){ addMsg('¿En qué más puedo ayudarte? 😊','bot'); setQuick('inicio'); }, 500);
            } else {
                mostrarTyping(respuestaDefault);
            }
        }

        function respuestaMenu(filtro) {
            var prods = KB.productos;
            if (filtro.includes('hamburguesa')||filtro.includes('rápida')) prods = prods.filter(function(p){ return p.cat==='Comidas rápidas'; });
            else if (filtro.includes('parrilla')||filtro.includes('bbq'))  prods = prods.filter(function(p){ return p.cat==='Parrilla'; });
            else if (filtro.includes('bebida'))                             prods = prods.filter(function(p){ return p.cat==='Bebidas'; });
            var html = '🍽️ <strong>Nuestro menú:</strong><br><br>';
            prods.forEach(function(p){ html += p.emoji+' <strong>'+p.nombre+'</strong> — '+p.precio+'<br>'; });
            html += '<br>¿Te gustaría hacer un pedido?';
            addMsg(html, 'bot'); setQuick('pedido');
        }

        function respuestaReserva() {
            addMsg('📅 ¡Genial! Para reservar una mesa necesitas tener una cuenta.', 'bot', 0);
            mostrarTyping(function(){
                addMsg('Con tu cuenta puedes:<br>'+KB.beneficios.join('<br>'), 'bot');
                mostrarTyping(function(){ addMsg('¿Ya tienes cuenta o quieres registrarte? 👇','bot'); setQuick('auth'); }, 800);
            }, 800);
        }

        function respuestaPedido() {
            addMsg('🛒 ¡Perfecto! Para realizar un pedido necesitas iniciar sesión.', 'bot', 0);
            mostrarTyping(function(){
                addMsg('Al registrarte obtienes:<br>'+KB.beneficios.join('<br>'), 'bot');
                mostrarTyping(function(){ addMsg('¿Qué prefieres hacer? 👇','bot'); setQuick('auth'); }, 800);
            }, 800);
        }

        function respuestaHorario() {
            addMsg('🕐 <strong>Horarios:</strong><br><br>'+KB.horarios+'<br><br>¡Te esperamos!', 'bot');
            setQuick('inicio');
        }

        function respuestaPrecios() {
            var html = '💰 <strong>Precios:</strong><br><br>';
            KB.productos.forEach(function(p){ html += p.emoji+' '+p.nombre+': <strong>'+p.precio+'</strong><br>'; });
            addMsg(html, 'bot'); setQuick('pedido');
        }

        function respuestaAyuda() {
            addMsg('❓ <strong>Puedo ayudarte con:</strong><br><br>🍔 Menú y productos<br>📅 Reservas de mesa<br>🛒 Cómo hacer pedidos<br>🕐 Horarios<br>📝 Registro e ingreso', 'bot');
            setQuick('ayuda');
        }

        function respuestaContacto() {
            addMsg('📞 <strong>Contáctanos:</strong><br><br>📍 Restaurante La Tribu · Colombia<br>🕐 '+KB.horarios+'<br><br>¡Estamos para servirte!', 'bot');
            setQuick('inicio');
        }

        function respuestaDefault() {
            var r = ['No entendí bien 😅 ¿Puedo ayudarte con el menú, reservas o pedidos?','¿Quieres ver el menú o hacer una reserva?','Puedo ayudarte con menú, reservas y pedidos. ¿Qué necesitas? 😊'];
            addMsg(r[Math.floor(Math.random()*r.length)], 'bot'); setQuick('inicio');
        }

        function enviarMensaje() {
            var input = document.getElementById('chat-input');
            var txt   = input.value.trim();
            if (!txt) return;
            input.value = '';
            addMsg(txt, 'user');
            var l = txt.toLowerCase();
            mostrarTyping(function() {
                if (l.match(/hola|buenas|hey|saludos/))           { addMsg('¡Hola! 👋 ¿En qué puedo ayudarte?','bot'); setQuick('inicio'); }
                else if (l.match(/menu|menú|comida|plato/))        respuestaMenu(l);
                else if (l.match(/reserva|mesa|reservar/))         respuestaReserva();
                else if (l.match(/pedido|pedir|orden|comprar/))    respuestaPedido();
                else if (l.match(/horario|hora|abierto|cierra/))   respuestaHorario();
                else if (l.match(/precio|costo|cuanto|valor/))     respuestaPrecios();
                else if (l.match(/gracias|perfecto|genial/))       { addMsg('¡Con gusto! 😊 ¿Algo más?','bot'); setQuick('inicio'); }
                else if (l.match(/login|ingresar|iniciar|sesion/)) { addMsg('Te llevo al inicio de sesión 🔑','bot'); setTimeout(function(){ window.location.href='views/usuarios/login.php'; },1200); }
                else if (l.match(/registro|registrar|cuenta/))     { addMsg('¡Vamos a crear tu cuenta! 📝','bot'); setTimeout(function(){ window.location.href='views/usuarios/registre.php'; },1200); }
                else if (l.match(/hamburguesa|burger/))            respuestaMenu('hamburguesa');
                else if (l.match(/bbq|costilla|parrilla/))         respuestaMenu('parrilla');
                else if (l.match(/bebida|limonada|jugo/))          respuestaMenu('bebida');
                else                                                respuestaDefault();
            });
        }

        // Notificación a los 3 segundos
        setTimeout(function() {
            if (!chatAbierto) document.getElementById('chat-notif').style.display = 'flex';
        }, 3000);
    </script>

</body>
</html>