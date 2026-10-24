<?php
// Iniciar sesión globalmente (cookie solo accesible por el servidor y no enviada desde otros sitios)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Cabeceras de seguridad básicas
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

$host = 'localhost';
$database = 'phone_catalog';
$user = 'root';
$password = ''; // Configuración habitual de XAMPP local

$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";

try {
    $conexion = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(500);
    exit('No se ha podido conectar con el catálogo. Inténtalo más tarde.');
}
