<?php
require_once __DIR__ . '/config/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Por favor, introduce el nombre de usuario y el correo electrónico.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } else {
        try {
            $stmt = $conexion->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'El nombre de usuario o el correo ya están registrados.';
            } else {
                $stmt = $conexion->prepare("INSERT INTO users (username, email, is_admin) VALUES (?, ?, 0)");
                $stmt->execute([$username, $email]);
                
                $_SESSION['user_id'] = $conexion->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['is_admin'] = 0;

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error en el registro: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Biblioteca de Móviles</title>
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
                        </span>
                        <a href="logout.php" style="color: #fff; text-decoration: none; font-size: 0.8rem; margin-left: 10px;">Cerrar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
            <h1>Registro de Usuario</h1>
            <p class="subtitulo">Crea tu cuenta con solo tu nombre y correo electrónico</p>
        </div>
    </header>

    <main class="contenedor espacio-cuerpo">
        <div class="tarjeta" style="max-width: 450px; margin: 0 auto; padding: 30px;">
            <?php if (!empty($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div style="margin-bottom: 15px;">
                    <label for="username" style="display: block; margin-bottom: 6px; font-weight: 600;">Nombre de usuario:</label>
                    <input type="text" id="username" name="username" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="email" style="display: block; margin-bottom: 6px; font-weight: 600;">Correo electrónico:</label>
                    <input type="email" id="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #18263b; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Registrarse</button>
            </form>

            <p style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
                ¿Ya tienes cuenta? <a href="login.php" style="color: #3264b3;">Iniciar sesión</a>
            </p>
        </div>
    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal</p>
        </div>
    </footer>
</body>
</html>