<?php
/**
 * ============================================================================
 * LOGIN.PHP · INICIO DE SESIÓN
 * Usuario + contraseña (se comparan con el hash guardado en la tabla users).
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// Si ya hay sesión iniciada, redirigir al inicio
if (usuario_logueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
$usuario_escrito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_escrito = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!csrf_valido()) {
        $error = 'El formulario ha caducado. Recarga la página e inténtalo de nuevo.';
    } elseif ($usuario_escrito === '' || $password === '') {
        $error = 'Introduce tu nombre de usuario y tu contraseña.';
    } else {
        try {
            $stmt = $conexion->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$usuario_escrito]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, (string)$user['password'])) {
                // Sesión nueva (evita que alguien reutilice un identificador de sesión anterior)
                session_regenerate_id(true);
                unset($_SESSION['csrf_token'], $_SESSION['esquema_ok_hasta']);

                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = ((int)$user['is_admin'] === 1) ? 1 : 0;

                // Si PHP ha mejorado el algoritmo de hash, se actualiza el guardado
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $conexion->prepare("UPDATE users SET password = ? WHERE id = ?")
                             ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }

                header('Location: index.php');
                exit;
            }

            // Mismo mensaje si falla el usuario o la contraseña (no se revela cuál) y una pequeña espera
            usleep(400000);
            $error = 'Usuario o contraseña incorrectos.';
        } catch (PDOException $e) {
            error_log('Error de login: ' . $e->getMessage());
            $error = 'No se ha podido iniciar sesión. Inténtalo de nuevo más tarde.';
        }
    }
}

// Datos de la cabecera común
$pagina_actual    = 'login';
$pagina_titulo    = 'Iniciar Sesión | Biblioteca de Móviles';
$pagina_h1        = 'Iniciar Sesión';
$pagina_subtitulo = 'Accede con tu usuario y contraseña';
$pagina_css       = ['auth'];

require __DIR__ . '/includes/header.php';
?>

    <main class="contenedor espacio-cuerpo">
        <div class="tarjeta auth-card">
            <?php if ($error !== ''): ?>
                <div class="alerta-error"><?= escapar($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <?= csrf_campo() ?>
                <div class="campo-auth">
                    <label for="username">Nombre de usuario:</label>
                    <input type="text" id="username" name="username" required autocomplete="username" value="<?= escapar($usuario_escrito) ?>">
                </div>
                <div class="campo-auth">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-auth">Entrar</button>
            </form>

            <p class="auth-enlace">
                ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
            </p>
        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
