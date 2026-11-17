<?php
/**
 * Configuración compartida — Fitness Life S.A.S
 * Este archivo NO se sirve al público: solo lo incluyen los endpoints.
 */

// ==============================================================================
// ⚠️ REEMPLAZA con la contraseña real de la base de datos
// ==============================================================================
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$isFitnessLife = (strpos($serverName, 'fitnesslifesas.com') !== false);
$isLocal = ($serverName === 'localhost' || $serverName === '127.0.0.1' || strpos($serverName, '.local') !== false || strpos($serverName, '192.168.') !== false || php_sapi_name() === 'cli');

if ($isFitnessLife) {
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'fitnessl_cotizaciones');
    define('DB_USER', 'fitnessl_user');
    define('DB_PASS', 'FitLife_2026*Prod!Db');
} else if ($isLocal) {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', 8889);
    define('DB_NAME', 'cotizacioneslifefitness');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
} else {
    define('DB_HOST', 'mysql.advantascience.com');
    define('DB_PORT', 3306);
    define('DB_NAME', 'cotizacioneslifefitness');
    define('DB_USER', 'lifefitnesdb');
    define('DB_PASS', 'JT-sq16cy21');
}

// Dominios autorizados a consumir la API.
$ALLOWED_ORIGINS = [
    'https://fitnesslifesas.com',
    'https://www.fitnesslifesas.com',
    'https://advantascience.com',
    'https://www.advantascience.com',
    'http://localhost',
    'http://localhost:8888',
];

function cors(array $allowed, $methods = 'GET, OPTIONS') {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: $methods");
    header("Access-Control-Allow-Headers: Content-Type");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function respond($code, $success, $message, $data = null) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Error de conexión: ' . $e->getMessage());
        respond(500, false, 'Error al conectar con la base de datos.');
    }
    return $pdo;
}
