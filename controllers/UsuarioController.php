<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    // ─────────────────────────────────────────────
    // Redirige de vuelta al formulario con una alerta
    // ─────────────────────────────────────────────
    private function volver(array $alert): void
    {
        $_SESSION['alert'] = $alert;
        header("Location: ../views/usuarios/registre.php");
        exit;
    }

    // ─────────────────────────────────────────────
    // Procesa el formulario de registro
    // ─────────────────────────────────────────────
    public function registrar(): void
    {
        // Solo acepta peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/registre.php");
            exit;
        }

        // ── 1. Recoger y limpiar datos del formulario ──
        $nombre   = trim($_POST['nombre']   ?? '');
        $correo   = trim($_POST['correo']   ?? '');
        $password =      $_POST['password'] ?? '';
        $rol      = trim($_POST['role']     ?? $_POST['rol'] ?? 'cliente');
        $telefono = trim($_POST['telefono'] ?? '');

        // ── 2. Validaciones ──────────────────────────
        if ($nombre === '' || $correo === '' || $password === '') {
            $this->volver([
                'icon'  => 'warning',
                'title' => 'Campos incompletos',
                'text'  => 'Debe completar todos los campos',
            ]);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->volver([
                'icon'  => 'error',
                'title' => 'Correo inválido',
                'text'  => 'Ingrese un correo válido',
            ]);
        }

        if (strlen($password) < 6) {
            $this->volver([
                'icon'  => 'warning',
                'title' => 'Contraseña inválida',
                'text'  => 'La contraseña debe tener al menos 6 caracteres',
            ]);
        }

        if ($rol === 'cliente' && $telefono === '') {
            $this->volver([
                'icon'  => 'warning',
                'title' => 'Teléfono requerido',
                'text'  => 'Debe ingresar el teléfono',
            ]);
        }

        // ── 3. Verificar si el correo ya existe ──────
        $usuario = new Usuario((new Database())->conectar());

        if ($usuario->existeCorreo($correo)) {
            $this->volver([
                'icon'  => 'error',
                'title' => 'Correo existente',
                'text'  => 'Este correo ya está registrado',
            ]);
        }

        // ── 4. Intentar registrar el usuario ─────────
        $resultado = $usuario->crear([
            'nombre'   => $nombre,
            'correo'   => $correo,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => $rol,
            'telefono' => $telefono,
        ]);

        // ── 5. Responder según el resultado ──────────
        if ($resultado === true) {
            $this->volver([
                'icon'     => 'success',
                'title'    => 'Registro exitoso',
                'text'     => 'Tu cuenta fue creada correctamente',
                'redirect' => 'login.php',
            ]);
        }

        $this->volver([
            'icon'  => 'error',
            'title' => 'Error',
            'text'  => $resultado,
        ]);
    }
}

// ── Punto de entrada ──────────────────────────────
(new UsuarioController())->registrar();