<?php
session_start();

require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
$dotenv->load();

// Database config from .env
$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$charset = 'utf8mb4';

// DSN (connection string)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES => false,               // Use real prepared statements
];

try {
    // Create PDO instance (connect to DB)
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    // Exit if connection failssr
    exit('Database connection failed' . $e->getMessage());
}
