<?php
/**
 * ============================================================================
 * REGISTER.PHP · REGISTRO DE USUARIOS
 * Nombre de usuario + contraseña (se guarda solo el hash, nunca la contraseña).
 * Los usuarios nuevos nunca son administradores.
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

if (usuario_logueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
$usuario_escrito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_escrito = trim((string)($_POST['username'] ?? ''));
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!csrf_valido()) {
        $error = 'El formulario ha caducado. Recarga la página e inténtalo de nuevo.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $usuario_escrito)) {
        $error = 'El nombre de usuario debe tener entre 3 y 30 caracteres: letras, números, punto, guion o guion bajo.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (strlen($password) > 72) {
        $error = 'La contraseña no puede superar los 72 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las dos contraseñas no coinciden.';
    } else {
        try {
            $stmt = $conexion->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$usuario_escrito]);
            if ($stmt->fetch()) {
                $error = 'Ese nombre de usuario ya está registrado.';
            } else {
                $conexion->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 0)")
                         ->execute([$usuario_escrito, password_hash($password, PASSWORD_DEFAULT)]);

                session_regenerate_id(true);
                unset($_SESSION['csrf_token'], $_SESSION['esquema_ok_hasta']);
                $_SESSION['user_id']  = (int)$conexion->lastInsertId();
                $_SESSION['username'] = $usuario_escrito;
                $_SESSION['is_admin'] = 0;

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Error de registro: ' . $e->getMessage());
            $error = 'No se ha podido completar el registro. Inténtalo de nuevo más tarde.';
        }
    }
}

// Datos de la cabecera común
$pagina_actual    = 'register';
$pagina_titulo    = 'Registro | Biblioteca de Móviles';
$pagina_h1        = 'Registro de Usuario';
$pagina_subtitulo = 'Crea tu cuenta con un nombre de usuario y una contraseña';
$pagina_css       = ['auth'];

require __DIR__ . '/includes/header.php';
?>

    <main class="contenedor espacio-cuerpo">
        <div class="tarjeta auth-card">
            <?php if ($error !== ''): ?>
                <div class="alerta-error"><?= escapar($error) ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <?= csrf_campo() ?>
                <div class="campo-auth">
                    <label for="username">Nombre de usuario:</label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="30" autocomplete="username" value="<?= escapar($usuario_escrito) ?>">
                </div>
                <div class="campo-auth">
                    <label for="password">Contraseña (mínimo 8 caracteres):</label>
                    <input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                </div>
                <div class="campo-auth">
                    <label for="password2">Repite la contraseña:</label>
                    <input type="password" id="password2" name="password2" required minlength="8" maxlength="72" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-auth">Registrarse</button>
            </form>

            <p class="auth-enlace">
                ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
            </p>
        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
