<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private function alerta(string $icon, string $title, string $text, string $to): void {
        $_SESSION['alert'] = ['icon'=>$icon, 'title'=>$title, 'text'=>$text];
        header("Location: $to");
        exit;
    }
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../views/usuarios/login.php"); exit; }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') $this->alerta('warning','Campos incompletos','Debe ingresar correo y contraseña','../views/usuarios/login.php');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $this->alerta('error','Correo inválido','Ingrese un correo electrónico válido','../views/usuarios/login.php');
        $usuario = (new Usuario((new Database())->conectar()))->obtenerPorEmail($email);
        if (!$usuario || !password_verify($password, $usuario['password'])) $this->alerta('error','Credenciales incorrectas','Correo o contraseña inválidos','../views/usuarios/login.php');
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id_usuario'=>$usuario['id_usuario'], 'nombre'=>$usuario['nombre'], 'correo'=>$usuario['correo'], 'role'=>$usuario['role']];
        $destinos = ['admin'=>'../views/dashboard/admin_dashboard.php','mesero'=>'../views/dashboard/mesero_dashboard.php','cliente'=>'../views/dashboard/cliente_dashboard.php'];
        header('Location: ' . ($destinos[$usuario['role']] ?? '../views/usuarios/login.php'));
        exit;
    }
    public function logout(): void {
        // Limpiar completamente la sesión
        $_SESSION = [];

        // Destruir la cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        // Headers que impiden que el navegador cachee la página
        // y que el botón "atrás" muestre contenido protegido
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

        header('Location: ../views/usuarios/login.php');
        exit;
    }
}
$controller = new AuthController();
($_GET['accion'] ?? 'login') === 'logout' ? $controller->logout() : $controller->login();
?>
