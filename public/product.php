<?php
require 'db.php';

class Product {
    public $id;
    public $name;
    public $description;
    public $price;
    public $email;
    public $category_id;

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addProduct($name, $description, $price, $email, $category_id) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, description, price, email, category_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$name, $description, $price, $email, $category_id]);
    }

    public function updateProduct($id, $name, $description, $price, $email, $category_id) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, email = ?, category_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([$name, $description, $price, $email, $category_id, $id]);
    }

    public function deleteProduct($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getProduct($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAllProducts() {
        $stmt = $this->pdo->query("
            SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll();
    }
}
