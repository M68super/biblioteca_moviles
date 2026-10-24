<?php
/**
 * ============================================================================
 * CABECERA COMÚN A TODAS LAS PÁGINAS
 *
 * Cada página define estas variables ANTES de incluir este archivo:
 *   $pagina_actual   -> 'index', 'admin', 'detalles', 'comparar', 'login', 'register'...
 *   $pagina_titulo   -> texto de la pestaña del navegador
 *   $pagina_h1       -> título grande de la cabecera
 *   $pagina_subtitulo-> (opcional) texto bajo el título
 *   $pagina_css      -> (opcional) hojas de estilo propias, sin extensión: ['index'] carga css/index.css
 *   $cabecera_personalizada -> (opcional) función que dibuja el contenido bajo la barra superior
 *                              en lugar del título (la usa detalles.php para la imagen y el hexágono)
 * ============================================================================
 */
$pagina_actual = $pagina_actual ?? '';
$pagina_titulo = $pagina_titulo ?? 'Catálogo de Smartphones · Estilo Kimovil';
$pagina_css    = $pagina_css ?? [];
$sesion_iniciada = usuario_logueado();
$nombre_usuario  = $sesion_iniciada ? (string)($_SESSION['username'] ?? '') : '';
$es_admin_header = es_admin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($pagina_titulo) ?></title>
    <link rel="stylesheet" href="css/general.css">
    <?php foreach ($pagina_css as $hoja): ?>
        <link rel="stylesheet" href="css/<?= escapar($hoja) ?>.css">
    <?php endforeach; ?>
</head>
<body>

    <header class="cabecera">
        <div class="contenedor">
            <div class="header-top-bar">
                <p class="sobrelinea">
                    <?php if ($pagina_actual === 'index'): ?>
                        CATÁLOGO TECNOLÓGICO ESTILO KIMOVIL
                    <?php else: ?>
                        <a href="index.php" class="enlace-volver">&larr; VOLVER AL CATÁLOGO</a>
                    <?php endif; ?>
                    <span><a href="comparar.php">⚖️ Ir al Comparador &rarr;</a></span>
                    <?php if ($es_admin_header): ?>
                        <span><a href="admin.php" class="enlace-admin-rojo">👑 Ir al Panel Admin &rarr;</a></span>
                    <?php endif; ?>
                </p>
                <div class="header-sesion">
                    <?php if ($sesion_iniciada): ?>
                        <span class="usuario-logueado">Hola, <strong><?= escapar($nombre_usuario) ?></strong>
                            <?php if ($es_admin_header): ?><span class="badge-admin">ADMIN</span><?php endif; ?>
                        </span>
                        <form method="POST" action="logout.php" class="form-logout">
                            <?= csrf_campo() ?>
                            <button type="submit" class="btn-sesion">Cerrar sesión</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn-sesion">Iniciar sesión</a>
                        <a href="register.php" class="btn-sesion btn-registro">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($cabecera_personalizada) && is_callable($cabecera_personalizada)): ?>
                <?php $cabecera_personalizada(); ?>
            <?php else: ?>
                <h1><?= escapar($pagina_h1 ?? 'Biblioteca de smartphones') ?></h1>
                <?php if (!empty($pagina_subtitulo)): ?>
                    <p class="subtitulo"><?= escapar($pagina_subtitulo) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
