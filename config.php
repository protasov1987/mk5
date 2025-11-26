<?php
// Общая конфигурация приложения
session_start();

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
    http_response_code(500);
    die('Не удалось подключиться к базе данных: ' . htmlspecialchars($e->getMessage()));
}

date_default_timezone_set('Europe/Moscow');
