<?php
require_once 'product.php';

class PhysicalProduct extends Product
{
    private $weight;
    private $dimensions;

    public function getWeight() { return $this->weight; }
    public function setWeight($w) { $this->weight = $w; }

    public function getDimensions() { return $this->dimensions; }
    public function setDimensions($d) { $this->dimensions = $d; }

    public function addProduct()
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, description, price, email, category_id, type, weight, dimensions, created_at)
            VALUES (?, ?, ?, ?, ?, 'Physical', ?, ?, NOW())
        ");
        return $stmt->execute([
            $this->getName(),
            $this->getDescription(),
            $this->getPrice(),
            $this->getMail(),
            $this->getCategoryId(),
            $this->weight,
            $this->dimensions
        ]);
    }

    public function updateProduct()
    {
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET name = ?, description = ?, price = ?, email = ?, category_id = ?, 
                type = 'Physical', weight = ?, dimensions = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $this->getName(),
            $this->getDescription(),
            $this->getPrice(),
            $this->getMail(),
            $this->getCategoryId(),
            $this->weight,
            $this->dimensions,
            $this->getId()
        ]);
    }

}