<?php
require_once __DIR__ . '/config/conexion.php';

// Si ya hay sesión iniciada, redirigir al inicio
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($identifier)) {
        $error = 'Por favor, introduce tu nombre de usuario o correo.';
    } else {
        try {
            $stmt = $conexion->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            // Acceso directo y blindado para el Administrador de prueba
            if ($identifier === 'admin' && ($password === 'admin' || (isset($user['password']) && password_verify($password, $user['password'])))) {
                $_SESSION['user_id'] = $user ? $user['id'] : 1;
                $_SESSION['username'] = 'admin';
                $_SESSION['email'] = $user ? $user['email'] : 'admin@biblioteca.local';
                $_SESSION['is_admin'] = 1;

                header('Location: index.php');
                exit;
            }

            if ($user) {
                $es_admin_bd = ((int)$user['is_admin'] === 1 || $user['username'] === 'admin');

                if ($es_admin_bd) {
                    if (empty($password) || (!password_verify($password, $user['password']) && $password !== 'admin')) {
                        $error = 'Contraseña de administrador incorrecta.';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['is_admin'] = 1;
                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = 0;
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Usuario o correo no encontrado. Regístrate primero.';
            }
        } catch (PDOException $e) {
            $error = 'Error al iniciar sesión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Biblioteca de Móviles</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <header class="cabecera">
        <div class="contenedor">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <p class="sobrelinea" style="margin: 0;"><a href="index.php" style="color: #a9c5ff; text-decoration: none;">&larr; VOLVER AL CATÁLOGO</a></p>
                <div>
                    <?php if (isset($_SESSION['username'])): ?>
                        <span style="color: #a9c5ff; font-size: 0.85rem;">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                            <?php if (!empty($_SESSION['is_admin'])): ?> <span style="background: #e74c3c; color: white; padding: 1px 5px; border-radius: 3px; font-size: 0.7rem;">ADMIN</span> <?php endif; ?>
                        </span>
                        <a href="logout.php" style="color: #fff; text-decoration: none; font-size: 0.8rem; margin-left: 10px;">Cerrar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
            <h1>Iniciar Sesión</h1>
            <p class="subtitulo">Acceso de usuarios y administradores</p>
        </div>
    </header>

    <main class="contenedor espacio-cuerpo">
        <div class="tarjeta" style="max-width: 450px; margin: 0 auto; padding: 30px;">
            <?php if (!empty($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div style="margin-bottom: 15px;">
                    <label for="identifier" style="display: block; margin-bottom: 6px; font-weight: 600;">Nombre de usuario o Correo:</label>
                    <input type="text" id="identifier" name="identifier" required placeholder="Ej: admin o tu usuario" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="password" style="display: block; margin-bottom: 6px; font-weight: 600;">Contraseña <span style="font-size: 0.8rem; font-weight: normal; color: #666;">(Solo Admin)</span>:</label>
                    <input type="password" id="password" name="password" placeholder="Solo si eres admin" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #18263b; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Entrar</button>
            </form>

            <p style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
                ¿No tienes cuenta? <a href="register.php" style="color: #3264b3;">Regístrate aquí</a>
            </p>
            <div style="margin-top: 20px; padding: 10px; background: #e8edf4; border-radius: 6px; font-size: 0.85rem; color: #596579;">
                <strong>Admin de prueba:</strong> Usuario: <code>admin</code> | Contraseña: <code>admin</code>
            </div>
        </div>
    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal estilo Kimovil</p>
        </div>
    </footer>
</body>
</html>