<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'cliente') {
    header('Location: ../usuarios/login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';

$db         = (new Database())->conectar();
$userModel  = new Usuario($db);
$idUsuario  = (int)$_SESSION['usuario']['id_usuario'];

$msgPerfil  = ['tipo' => '', 'texto' => ''];
$msgCorreo  = ['tipo' => '', 'texto' => ''];
$msgPass    = ['tipo' => '', 'texto' => ''];

/* ══════════════════════════════════════════
   ACTUALIZAR DATOS PERSONALES
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'perfil') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === '') {
        $msgPerfil = ['tipo' => 'error', 'texto' => 'El nombre no puede estar vacío.'];
    } elseif ($userModel->actualizarPerfil($idUsuario, $nombre, $telefono)) {
        // Actualizar sesión
        $_SESSION['usuario']['nombre']   = $nombre;
        $_SESSION['usuario']['telefono'] = $telefono;
        $msgPerfil = ['tipo' => 'ok', 'texto' => 'Datos actualizados correctamente.'];
    } else {
        $msgPerfil = ['tipo' => 'error', 'texto' => 'Error al actualizar los datos.'];
    }
}

/* ══════════════════════════════════════════
   CAMBIAR CORREO
   Requiere confirmar el correo actual
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'correo') {
    $correoActual    = trim($_POST['correo_actual']    ?? '');
    $correoNuevo     = trim($_POST['correo_nuevo']     ?? '');
    $correoConfirmar = trim($_POST['correo_confirmar'] ?? '');

    $usuarioActual = $userModel->obtenerPorId($idUsuario);

    if ($correoActual === '') {
        $msgCorreo = ['tipo' => 'error', 'texto' => 'Debes ingresar tu correo actual para confirmar.'];
    } elseif ($usuarioActual['correo'] !== $correoActual) {
        $msgCorreo = ['tipo' => 'error', 'texto' => 'El correo actual no coincide con el registrado.'];
    } elseif (!filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {
        $msgCorreo = ['tipo' => 'error', 'texto' => 'El nuevo correo no tiene un formato válido.'];
    } elseif ($correoNuevo !== $correoConfirmar) {
        $msgCorreo = ['tipo' => 'error', 'texto' => 'Los correos nuevos no coinciden.'];
    } elseif ($correoNuevo === $correoActual) {
        $msgCorreo = ['tipo' => 'error', 'texto' => 'El nuevo correo es igual al actual.'];
    } else {
        $ok = $userModel->cambiarCorreo($idUsuario, $correoNuevo);
        if ($ok) {
            $_SESSION['usuario']['correo'] = $correoNuevo;
            $msgCorreo = ['tipo' => 'ok', 'texto' => 'Correo actualizado correctamente.'];
        } else {
            $msgCorreo = ['tipo' => 'error', 'texto' => 'Ese correo ya está en uso por otra cuenta.'];
        }
    }
}

/* ══════════════════════════════════════════
   CAMBIAR CONTRASEÑA
   Requiere correo + contraseña actual + nueva x2
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'password') {
    $correoVerif  = trim($_POST['correo_verificacion'] ?? '');
    $passActual   = $_POST['password_actual']          ?? '';
    $passNueva    = $_POST['password_nueva']           ?? '';
    $passConfirm  = $_POST['password_confirmar']       ?? '';

    $usuarioActual = $userModel->obtenerPorId($idUsuario);

    if ($correoVerif === '') {
        $msgPass = ['tipo' => 'error', 'texto' => 'Debes ingresar tu correo para verificar tu identidad.'];
    } elseif ($usuarioActual['correo'] !== $correoVerif) {
        $msgPass = ['tipo' => 'error', 'texto' => 'El correo ingresado no coincide con tu cuenta.'];
    } elseif ($passNueva !== $passConfirm) {
        $msgPass = ['tipo' => 'error', 'texto' => 'Las contraseñas nuevas no coinciden.'];
    } else {
        $resultado = $userModel->cambiarPassword($idUsuario, $passActual, $passNueva);
        $msgPass   = ['tipo' => $resultado['ok'] ? 'ok' : 'error', 'texto' => $resultado['msg']];
    }
}

// Recargar datos frescos de BD
$usuario = $userModel->obtenerPorId($idUsuario);
$titulo  = 'Mi Cuenta';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<style>
.cuenta-card {
    background: rgba(255,247,237,.95);
    border: 1px solid rgba(251,146,60,.18);
    border-radius: 22px;
    padding: 1.75rem;
}
.inp-cuenta {
    width: 100%;
    padding: .8rem 1rem;
    border: 2px solid #e7e5e4;
    border-radius: 14px;
    font-size: .95rem;
    outline: none;
    background: #fafaf9;
    transition: border-color .2s, box-shadow .2s;
}
.inp-cuenta:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234,88,12,.1);
    background: #fff;
}
.inp-cuenta:disabled {
    background: #f5f5f4;
    color: #a8a29e;
    cursor: not-allowed;
}
.lbl { display:block; font-size:.75rem; font-weight:700; color:#78716c; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.4rem; }
.btn-guardar {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .75rem 1.75rem; border-radius: 14px; border: none;
    background: linear-gradient(135deg, #ea580c, #f59e0b);
    color: #fff; font-weight: 900; font-size: .95rem; cursor: pointer;
    box-shadow: 0 4px 14px rgba(234,88,12,.3);
    transition: opacity .2s, transform .15s;
}
.btn-guardar:hover { opacity: .9; transform: translateY(-1px); }
.alerta-ok    { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; border-radius:12px; padding:.75rem 1rem; font-weight:700; font-size:.88rem; display:flex; align-items:center; gap:.5rem; }
.alerta-error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius:12px; padding:.75rem 1rem; font-weight:700; font-size:.88rem; display:flex; align-items:center; gap:.5rem; }
.seccion-titulo { font-size:1.1rem; font-weight:900; color:#1c1917; display:flex; align-items:center; gap:.6rem; margin-bottom:1.25rem; }
.seccion-ico { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.pass-wrap { position:relative; }
.pass-wrap .inp-cuenta { padding-right: 2.75rem; }
.toggle-pass { position:absolute; right:.85rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#a8a29e; font-size:.95rem; padding:0; }
.toggle-pass:hover { color:#ea580c; }
.req-item { display:flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:600; }
.req-ok  { color:#15803d; }
.req-no  { color:#a8a29e; }
</style>

<div class="space-y-6">

    <!-- ══ ENCABEZADO ══ -->
    <div style="background:rgba(28,25,23,.82);backdrop-filter:blur(16px);border:1px solid rgba(251,146,60,.25);border-radius:24px;padding:1.5rem 2rem;">
        <div class="flex items-center gap-5">
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.75rem;font-weight:900;flex-shrink:0;box-shadow:0 6px 20px rgba(234,88,12,.4);">
                <?= strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <p class="text-orange-300 font-bold uppercase tracking-widest text-xs mb-1">Mi Cuenta</p>
                <h1 class="text-2xl font-black text-white"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></h1>
                <p class="text-orange-200 text-sm mt-0.5">
                    <i class="fa-solid fa-envelope mr-1"></i><?= htmlspecialchars($usuario['correo'] ?? '') ?>
                    &nbsp;·&nbsp;
                    <i class="fa-solid fa-user mr-1"></i><?= htmlspecialchars(ucfirst($usuario['role'] ?? '')) ?>
                    &nbsp;·&nbsp;
                    <i class="fa-solid fa-calendar mr-1"></i>Desde <?= isset($usuario['created_at']) ? date('d/m/Y', strtotime($usuario['created_at'])) : '—' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ══ DATOS PERSONALES ══ -->
    <div class="cuenta-card">
        <div class="seccion-titulo">
            <div class="seccion-ico" style="background:linear-gradient(135deg,#ea580c,#f59e0b);color:#fff;">
                <i class="fa-solid fa-user"></i>
            </div>
            Datos Personales
        </div>

        <?php if ($msgPerfil['texto']): ?>
            <div class="<?= $msgPerfil['tipo']==='ok' ? 'alerta-ok' : 'alerta-error' ?> mb-4">
                <i class="fa-solid fa-<?= $msgPerfil['tipo']==='ok' ? 'circle-check' : 'circle-exclamation' ?>"></i>
                <?= htmlspecialchars($msgPerfil['texto']) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="accion" value="perfil">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="lbl"><i class="fa-solid fa-user mr-1 text-orange-500"></i> Nombre completo *</label>
                    <input type="text" name="nombre" class="inp-cuenta" required
                        value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
                        placeholder="Tu nombre completo">
                </div>
                <div>
                    <label class="lbl"><i class="fa-solid fa-phone mr-1 text-orange-500"></i> Teléfono</label>
                    <input type="tel" name="telefono" class="inp-cuenta"
                        value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
                        placeholder="Ej. 3001234567">
                </div>
                <div>
                    <label class="lbl"><i class="fa-solid fa-envelope mr-1 text-orange-500"></i> Correo electrónico</label>
                    <input type="email" class="inp-cuenta" disabled
                        value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>">
                    <p style="font-size:.72rem;color:#a8a29e;margin-top:.3rem;">
                        <i class="fa-solid fa-lock mr-1"></i> Para cambiar el correo usa la sección de abajo
                    </p>
                </div>
                <div>
                    <label class="lbl"><i class="fa-solid fa-id-badge mr-1 text-orange-500"></i> Rol</label>
                    <input type="text" class="inp-cuenta" disabled
                        value="<?= htmlspecialchars(ucfirst($usuario['role'] ?? '')) ?>">
                </div>
            </div>
            <button type="submit" class="btn-guardar">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
        </form>
    </div>

    <!-- ══ CAMBIAR CORREO ══ -->
    <div class="cuenta-card">
        <div class="seccion-titulo">
            <div class="seccion-ico" style="background:linear-gradient(135deg,#0ea5e9,#0369a1);color:#fff;">
                <i class="fa-solid fa-envelope"></i>
            </div>
            Cambiar Correo Electrónico
        </div>

        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#1d4ed8;display:flex;align-items:flex-start;gap:.5rem;">
            <i class="fa-solid fa-circle-info mt-0.5 flex-shrink-0"></i>
            <span>Para cambiar tu correo debes confirmar el correo actual registrado en tu cuenta. El nuevo correo se usará para iniciar sesión.</span>
        </div>

        <?php if ($msgCorreo['texto']): ?>
            <div class="<?= $msgCorreo['tipo']==='ok' ? 'alerta-ok' : 'alerta-error' ?> mb-4">
                <i class="fa-solid fa-<?= $msgCorreo['tipo']==='ok' ? 'circle-check' : 'circle-exclamation' ?>"></i>
                <?= htmlspecialchars($msgCorreo['texto']) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="accion" value="correo">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="lbl"><i class="fa-solid fa-shield-halved mr-1 text-blue-500"></i> Correo actual *</label>
                    <input type="email" name="correo_actual" class="inp-cuenta" required
                        placeholder="Tu correo actual"
                        style="border-color:#bfdbfe;"
                        onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#bfdbfe'">
                </div>
                <div>
                    <label class="lbl"><i class="fa-solid fa-envelope mr-1 text-blue-500"></i> Nuevo correo *</label>
                    <input type="email" name="correo_nuevo" class="inp-cuenta" required
                        placeholder="nuevo@correo.com"
                        onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e7e5e4'">
                </div>
                <div>
                    <label class="lbl"><i class="fa-solid fa-envelope-circle-check mr-1 text-blue-500"></i> Confirmar nuevo *</label>
                    <input type="email" name="correo_confirmar" class="inp-cuenta" required
                        placeholder="Repite el nuevo correo"
                        onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e7e5e4'">
                </div>
            </div>
            <button type="submit" class="btn-guardar" style="background:linear-gradient(135deg,#0ea5e9,#0369a1);box-shadow:0 4px 14px rgba(14,165,233,.3);">
                <i class="fa-solid fa-envelope-circle-check"></i> Actualizar correo
            </button>
        </form>
    </div>

    <!-- ══ CAMBIAR CONTRASEÑA ══ -->
    <div class="cuenta-card">
        <div class="seccion-titulo">
            <div class="seccion-ico" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;">
                <i class="fa-solid fa-lock"></i>
            </div>
            Cambiar Contraseña
        </div>

        <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#7c3aed;display:flex;align-items:flex-start;gap:.5rem;">
            <i class="fa-solid fa-shield-halved mt-0.5 flex-shrink:0"></i>
            <span>Por seguridad debes ingresar tu <strong>correo registrado</strong> y tu <strong>contraseña actual</strong> para poder establecer una nueva contraseña.</span>
        </div>

        <?php if ($msgPass['texto']): ?>
            <div class="<?= $msgPass['tipo']==='ok' ? 'alerta-ok' : 'alerta-error' ?> mb-4">
                <i class="fa-solid fa-<?= $msgPass['tipo']==='ok' ? 'circle-check' : 'circle-exclamation' ?>"></i>
                <?= htmlspecialchars($msgPass['texto']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="formPassword">
            <input type="hidden" name="accion" value="password">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

                <!-- Verificación de identidad -->
                <div class="md:col-span-2">
                    <p style="font-size:.8rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid #f5f0eb;">
                        <i class="fa-solid fa-shield-halved text-purple-500 mr-1"></i> Verificación de identidad
                    </p>
                </div>

                <div>
                    <label class="lbl"><i class="fa-solid fa-envelope mr-1 text-purple-500"></i> Tu correo registrado *</label>
                    <input type="email" name="correo_verificacion" class="inp-cuenta" required
                        placeholder="Ingresa tu correo para verificar"
                        style="border-color:#e9d5ff;"
                        onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e9d5ff'">
                </div>

                <div>
                    <label class="lbl"><i class="fa-solid fa-lock mr-1 text-purple-500"></i> Contraseña actual *</label>
                    <div class="pass-wrap">
                        <input type="password" name="password_actual" id="pass_actual" class="inp-cuenta" required
                            placeholder="Tu contraseña actual"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e7e5e4'">
                        <button type="button" class="toggle-pass" onclick="togglePass('pass_actual', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Nueva contraseña -->
                <div class="md:col-span-2" style="margin-top:.25rem;">
                    <p style="font-size:.8rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid #f5f0eb;">
                        <i class="fa-solid fa-key text-purple-500 mr-1"></i> Nueva contraseña
                    </p>
                </div>

                <div>
                    <label class="lbl"><i class="fa-solid fa-key mr-1 text-purple-500"></i> Nueva contraseña *</label>
                    <div class="pass-wrap">
                        <input type="password" name="password_nueva" id="pass_nueva" class="inp-cuenta" required
                            placeholder="Mínimo 6 caracteres"
                            oninput="validarRequisitos(this.value)"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e7e5e4'">
                        <button type="button" class="toggle-pass" onclick="togglePass('pass_nueva', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <!-- Requisitos en tiempo real -->
                    <div style="margin-top:.6rem;display:flex;flex-direction:column;gap:.25rem;" id="requisitos">
                        <div class="req-item req-no" id="req-len"><i class="fa-solid fa-circle text-xs"></i> Mínimo 6 caracteres</div>
                        <div class="req-item req-no" id="req-num"><i class="fa-solid fa-circle text-xs"></i> Al menos un número</div>
                        <div class="req-item req-no" id="req-may"><i class="fa-solid fa-circle text-xs"></i> Al menos una mayúscula</div>
                    </div>
                </div>

                <div>
                    <label class="lbl"><i class="fa-solid fa-key mr-1 text-purple-500"></i> Confirmar nueva contraseña *</label>
                    <div class="pass-wrap">
                        <input type="password" name="password_confirmar" id="pass_confirm" class="inp-cuenta" required
                            placeholder="Repite la nueva contraseña"
                            oninput="validarCoincidencia()"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e7e5e4'">
                        <button type="button" class="toggle-pass" onclick="togglePass('pass_confirm', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div id="msg-coincide" style="margin-top:.5rem;font-size:.78rem;font-weight:700;display:none;"></div>
                </div>

            </div>

            <button type="submit" class="btn-guardar" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);box-shadow:0 4px 14px rgba(124,58,237,.3);">
                <i class="fa-solid fa-lock"></i> Cambiar contraseña
            </button>
        </form>
    </div>

</div><!-- /space-y-6 -->

<script>
/* ── Mostrar/ocultar contraseña ── */
function togglePass(inputId, btn) {
    var inp  = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

/* ── Validar requisitos en tiempo real ── */
function validarRequisitos(val) {
    setReq('req-len', val.length >= 6);
    setReq('req-num', /\d/.test(val));
    setReq('req-may', /[A-Z]/.test(val));
    validarCoincidencia();
}
function setReq(id, ok) {
    var el = document.getElementById(id);
    el.className = 'req-item ' + (ok ? 'req-ok' : 'req-no');
    el.querySelector('i').className = 'fa-solid ' + (ok ? 'fa-circle-check text-xs' : 'fa-circle text-xs');
}

/* ── Validar coincidencia ── */
function validarCoincidencia() {
    var nueva   = document.getElementById('pass_nueva').value;
    var confirm = document.getElementById('pass_confirm').value;
    var msg     = document.getElementById('msg-coincide');
    if (confirm === '') { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (nueva === confirm) {
        msg.style.color = '#15803d';
        msg.innerHTML   = '<i class="fa-solid fa-circle-check mr-1"></i> Las contraseñas coinciden';
    } else {
        msg.style.color = '#b91c1c';
        msg.innerHTML   = '<i class="fa-solid fa-circle-xmark mr-1"></i> Las contraseñas no coinciden';
    }
}

/* ── Scroll a la sección con error ── */
<?php if ($msgPerfil['tipo'] === 'error'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('[name="nombre"]').scrollIntoView({behavior:'smooth', block:'center'});
    });
<?php elseif ($msgPass['tipo'] === 'error'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('formPassword').scrollIntoView({behavior:'smooth', block:'start'});
    });
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
