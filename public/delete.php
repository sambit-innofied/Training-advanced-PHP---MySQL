<?php
require 'db.php';
require 'product.php';

// Create Product object
$productObj = new Product($pdo);

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Get product ID from POST data
$id = $_POST['id'] ?? null;

// If ID is provided, delete that product
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
}

// Redirect back to home page after delete
header('Location: index.php');
exit;
