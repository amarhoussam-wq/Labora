<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// LABORA - Core Configuration
// ============================================================
define('ROOT', __DIR__);

// Auto-detect BASE_URL based on server environment
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $projRoot = str_replace('\\', '/', realpath(__DIR__));
    if ($docRoot && str_starts_with($projRoot, $docRoot)) {
        $sub = trim(substr($projRoot, strlen($docRoot)), '/');
        $base = $sub === '' ? '/' : '/' . $sub . '/';
    } else {
        $base = '/labora/';
    }
    define('BASE_URL', $base);
}

// Database connection
$tmpConfig = '/tmp/labora_db_config.php';
$localConfigFile = ROOT . '/db_config.php';

if (file_exists($tmpConfig)) {
    $savedConfig = require $tmpConfig;
} elseif (file_exists($localConfigFile)) {
    $savedConfig = require $localConfigFile;
} else {
    $savedConfig = [];
}

$defaultHost = (PHP_OS_FAMILY === 'Windows') ? 'localhost' : 'e880ooggksgs0004sosggows';

$host = $savedConfig['host'] ?? (getenv('DB_HOST') ?: $defaultHost);
// If running in Docker on Coolify and host is set to "mysql", automatically use the real container host
if (PHP_OS_FAMILY !== 'Windows' && ($host === 'mysql' || $host === 'localhost' || empty($host))) {
    $host = 'e880ooggksgs0004sosggows';
}
$port = (int)($savedConfig['port'] ?? (getenv('DB_PORT') ?: 3306));
$db   = $savedConfig['name'] ?? (getenv('DB_NAME') ?: 'labora');
$user = $savedConfig['user'] ?? (getenv('DB_USER') ?: 'root');
$pass = $savedConfig['pass'] ?? (getenv('DB_PASS') ?: '');

try {
    $conn = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (Exception $e) {
    // If 'labora' database does not exist yet, we catch gracefully
    $conn = null;
    $db_connection_error = $e->getMessage();
}

// Load shared modules
require_once ROOT . '/includes/functions.php';
require_once ROOT . '/includes/auth.php';
