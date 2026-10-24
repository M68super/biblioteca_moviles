<?php
/**
 * ============================================================================
 * LOGOUT.PHP · CIERRE DE SESIÓN
 * Solo funciona por POST con token de seguridad (el botón "Cerrar sesión" de la cabecera).
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// Si alguien entra escribiendo la dirección, simplemente se le lleva al catálogo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
exigir_csrf();

// Limpiar todas las variables de sesión
$_SESSION = [];

// Destruir la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destruir la sesión en el servidor
session_destroy();

// Redirigir limpiamente al catálogo principal
header('Location: index.php');
exit;
