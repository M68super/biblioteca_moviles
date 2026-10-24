<?php
/**
 * ============================================================================
 * CREAR_ADMIN.PHP · CREACIÓN DEL PRIMER ADMINISTRADOR
 * Sustituye al antiguo acceso "admin / admin". Solo funciona mientras NO exista
 * ningún administrador con una contraseña válida; en cuanto existe uno, la página
 * se desactiva sola. Aun así, BORRA ESTE ARCHIVO del servidor cuando lo hayas usado.
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$error = '';
$creado = false;
$ya_hay_admin = false;
$usuario_escrito = 'admin';

try {
    // ¿Existe ya un administrador con una contraseña guardada correctamente (hash)?
    $admins = $conexion->query("SELECT password FROM users WHERE is_admin = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $hash) {
        $info = password_get_info((string)$hash);
        if (!empty($info['algo'])) {
            $ya_hay_admin = true;
            break;
        }
    }

    if (!$ya_hay_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario_escrito = trim((string)($_POST['username'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        if (!csrf_valido()) {
            $error = 'El formulario ha caducado. Recarga la página e inténtalo de nuevo.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $usuario_escrito)) {
            $error = 'El nombre de usuario debe tener entre 3 y 30 caracteres: letras, números, punto, guion o guion bajo.';
        } elseif (strlen($password) < 10) {
            $error = 'La contraseña del administrador debe tener al menos 10 caracteres.';
        } elseif (strlen($password) > 72) {
            $error = 'La contraseña no puede superar los 72 caracteres.';
        } elseif ($password !== $password2) {
            $error = 'Las dos contraseñas no coinciden.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $conexion->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $st->execute([$usuario_escrito]);
            $existente = $st->fetch();
            if ($existente) {
                // Si ese usuario ya existía (por ejemplo un "admin" antiguo), se le pone la contraseña nueva y se le da rol de admin
                $conexion->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE id = ?")->execute([$hash, $existente['id']]);
            } else {
                $conexion->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 1)")->execute([$usuario_escrito, $hash]);
            }
            $creado = true;
        }
    }
} catch (PDOException $e) {
    error_log('Error al crear el administrador: ' . $e->getMessage());
    $error = 'No se ha podido acceder a la tabla de usuarios. Comprueba la base de datos.';
}

// Datos de la cabecera común
$pagina_actual    = 'crear_admin';
$pagina_titulo    = 'Crear administrador | Biblioteca de Móviles';
$pagina_h1        = 'Crear el primer administrador';
$pagina_subtitulo = 'Esta página se desactiva sola cuando ya existe un administrador';
$pagina_css       = ['auth'];

require __DIR__ . '/includes/header.php';
?>

    <main class="contenedor espacio-cuerpo">
        <div class="tarjeta auth-card">
            <?php if ($creado): ?>
                <div class="alerta-exito">
                    Administrador «<?= escapar($usuario_escrito) ?>» creado correctamente.
                </div>
                <p>Ya puedes <a href="login.php">iniciar sesión</a>. Por seguridad, <strong>borra ahora el archivo <code>crear_admin.php</code></strong> del servidor.</p>
            <?php elseif ($ya_hay_admin): ?>
                <p>Ya existe un administrador, así que esta página está desactivada.</p>
                <p>Por seguridad, <strong>borra el archivo <code>crear_admin.php</code></strong> del servidor.</p>
                <p><a href="login.php">Ir a iniciar sesión</a></p>
            <?php else: ?>
                <?php if ($error !== ''): ?>
                    <div class="alerta-error"><?= escapar($error) ?></div>
                <?php endif; ?>
                <form action="crear_admin.php" method="POST">
                    <?= csrf_campo() ?>
                    <div class="campo-auth">
                        <label for="username">Nombre de usuario del administrador:</label>
                        <input type="text" id="username" name="username" required minlength="3" maxlength="30" autocomplete="username" value="<?= escapar($usuario_escrito) ?>">
                    </div>
                    <div class="campo-auth">
                        <label for="password">Contraseña (mínimo 10 caracteres):</label>
                        <input type="password" id="password" name="password" required minlength="10" maxlength="72" autocomplete="new-password">
                    </div>
                    <div class="campo-auth">
                        <label for="password2">Repite la contraseña:</label>
                        <input type="password" id="password2" name="password2" required minlength="10" maxlength="72" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-auth">Crear administrador</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
