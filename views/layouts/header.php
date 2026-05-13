<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$titulo = $titulo ?? 'Sistema Restaurante';

// Headers anti-caché: impiden que el navegador guarde páginas protegidas
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
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
        /* ══════════════════════════════════════════
           RESET BASE
        ══════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            height: 100%;
        }

        body {
            min-height: 100%;
            background:
                linear-gradient(rgba(28,25,23,.90), rgba(28,25,23,.94)),
                url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=2000&q=80')
                center center / cover fixed;
            color: #1e293b;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }

        /* ══════════════════════════════════════════
           LAYOUT RAÍZ
           Sidebar fijo a la izquierda.
           Columna derecha ocupa el resto y usa
           flex-column para empujar el footer abajo.
        ══════════════════════════════════════════ */
        #app {
            display: flex;
            min-height: 100vh;   /* ocupa al menos toda la pantalla */
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 288px;
            flex-shrink: 0;
            position: sticky;    /* se queda fijo mientras scrolleas */
            top: 0;
            height: 100vh;       /* siempre visible */
            overflow-y: auto;
            z-index: 40;
        }

        /* ── Columna derecha ── */
        #col-right {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;   /* al menos toda la pantalla */
        }

        /* ── Header ── */
        #top-header {
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        /* ── Contenido: crece para empujar el footer ── */
        #content-area {
            flex: 1;             /* ocupa todo el espacio disponible */
            padding: 2rem;
        }

        /* ── Footer: siempre al fondo ── */
        #bottom-footer {
            flex-shrink: 0;
            margin-top: auto;    /* doble seguro: empuja al fondo */
        }

        /* ══════════════════════════════════════════
           SCROLLBAR PERSONALIZADA
        ══════════════════════════════════════════ */
        ::-webkit-scrollbar          { width: 6px; }
        ::-webkit-scrollbar-track    { background: #1c1917; }
        ::-webkit-scrollbar-thumb    { background: linear-gradient(#ea580c, #f59e0b); border-radius: 999px; }

        ::selection { background: #ea580c; color: #fff; }
    </style>
</head>

<body>
<div id="app">
