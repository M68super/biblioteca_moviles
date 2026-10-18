<?php
// Iniciar sesión globalmente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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