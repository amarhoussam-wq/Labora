<?php
session_start();

// Copy this file to config.php or set these values as environment variables
// on your hosting provider.
$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'ordinance';
$user = getenv('DB_USER') ?: 'your_database_user';
$password = getenv('DB_PASS') ?: 'your_database_password';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die('Database connection failed. Please check your configuration.');
}
?>
