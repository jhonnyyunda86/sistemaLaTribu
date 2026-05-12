<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$titulo = $titulo ?? 'Sistema Restaurante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> | La Tribu</title>

    <link rel="shortcut icon" type="image/png" href="../../img/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ── Fondo global ── */
        html, body {
            height: 100%;
            overflow: hidden; /* el scroll lo maneja solo el área de contenido */
        }
        body {
            background:
                linear-gradient(rgba(28,25,23,.90), rgba(28,25,23,.94)),
                url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #1e293b;
        }

        /* ── Contenedor raíz: ocupa toda la pantalla ── */
        #app {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Sidebar fijo a la izquierda ── */
        #sidebar {
            width: 288px;          /* w-72 */
            flex-shrink: 0;
            height: 100vh;
            overflow-y: auto;
            position: sticky;
            top: 0;
            left: 0;
            z-index: 40;
        }

        /* ── Columna derecha: header + contenido + footer ── */
        #col-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            min-width: 0;
        }

        /* ── Header fijo arriba ── */
        #top-header {
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        /* ── Área de contenido: único elemento con scroll ── */
        #content-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        /* ── Footer fijo abajo ── */
        #bottom-footer {
            flex-shrink: 0;
        }

        /* ── Scrollbar personalizada ── */
        #sidebar::-webkit-scrollbar,
        #content-area::-webkit-scrollbar { width: 6px; }
        #sidebar::-webkit-scrollbar-track,
        #content-area::-webkit-scrollbar-track { background: #1c1917; }
        #sidebar::-webkit-scrollbar-thumb,
        #content-area::-webkit-scrollbar-thumb {
            background: linear-gradient(#ea580c, #f59e0b);
            border-radius: 999px;
        }

        ::selection { background: #ea580c; color: white; }
    </style>
</head>

<body class="antialiased">
<div id="app">
