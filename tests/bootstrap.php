<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host    = $_ENV['DB_HOST'];
$db      = $_ENV['DB_NAME'];
$charset = "utf8mb4";

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Make $pdo available globally
$GLOBALS['pdo'] = $pdo;
