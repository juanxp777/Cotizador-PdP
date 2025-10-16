<?php
// config.php - Configuración de conexión a base de datos

// IMPORTANTE: Cambiar estos valores por los de tu hosting Hostinger
define('DB_HOST', 'localhost'); // Usualmente localhost en Hostinger
define('DB_NAME', 'u196943154_coticlaude'); // Nombre de tu base de datos
define('DB_USER', 'u196943154_juan'); // Usuario de MySQL en Hostinger
define('DB_PASS', '6@9ZdKtckN6ACSi'); // Contraseña de MySQL en Hostinger
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('SITE_URL', 'https://pdp.com.co'); // Tu dominio
define('SITE_NAME', 'Sistema de Cotización');
define('EMPRESA_NOMBRE', 'PdP Plotter Diseno y Publicidad');

// Zona horaria
date_default_timezone_set('America/Bogota');

// Función para conectar a la base de datos
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Iniciar sesión
session_start();

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para requerir login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Función para formatear moneda
function formatCurrency($amount) {
    return '$' . number_format($amount, 0, ',', '.');
}

// Función para generar folio único
function generateFolio() {
    return 'COT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}
?>