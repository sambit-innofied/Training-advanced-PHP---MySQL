<?php
require_once 'product.php';

// Class for Digital Products (inherits from Product)
class DigitalProduct extends Product
{
    private $fileSize;
    private $downloadLink; 

    // Getters and setters
    public function getFileSize() { return $this->fileSize; }
    public function setFileSize($f) { $this->fileSize = $f; }

    public function getDownloadLink() { return $this->downloadLink; }
    public function setDownloadLink($link) { $this->downloadLink = $link; }

    // Insert a digital product into DB
    public function addProduct()
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products 
                (name, description, price, email, category_id, type, file_size, download_link, created_at)
            VALUES (?, ?, ?, ?, ?, 'Digital', ?, ?, NOW())
        ");
        return $stmt->execute([
            $this->getName(),
            $this->getDescription(),
            $this->getPrice(),
            $this->getMail(),
            $this->getCategoryId(),
            $this->fileSize,
            $this->downloadLink
        ]);
    }

    // Update an existing digital product in DB
    public function updateProduct()
    {
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET name = ?, description = ?, price = ?, email = ?, category_id = ?, 
                type = 'Digital', file_size = ?, download_link = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $this->getName(),
            $this->getDescription(),
            $this->getPrice(),
            $this->getMail(),
            $this->getCategoryId(),
            $this->fileSize,
            $this->downloadLink,
            $this->getId()
        ]);
    }

}
