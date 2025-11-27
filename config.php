<?php
// Общая конфигурация приложения
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Настройки базы данных для Timeweb
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'cc226439_bd';
$db_user = getenv('DB_USER') ?: 'cc226439_bd';
$db_pass = getenv('DB_PASS') ?: '12345';
$db_port = getenv('DB_PORT') ?: '3306';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Не удалось подключиться к базе данных. Проверьте параметры подключения в config.php.');
}

date_default_timezone_set('Europe/Moscow');
