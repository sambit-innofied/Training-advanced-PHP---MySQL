<?php
require 'db.php';

class Product
{
    private $id;
    private $name;
    private $description;
    private $price;
    private $email;
    private $category_id;
    private $product_type;
    private $file_size;
    private $download_link;
    private $weight;
    private $dimensions;

    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    //getters and setters
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getMail()
    {
        return $this->email;
    }

    public function setMail($email)
    {
        $this->email = $email;
    }

    public function getCategoryId()
    {
        return $this->category_id;
    }

    public function setCategoryId($cat)
    {
        $this->category_id = $cat;
    }

    public function getProductType()
    {
        return $this->product_type;
    }

    public function setProductType($type)
    {
        $this->product_type = $type;
    }

    public function getFileSize()
    {
        return $this->file_size;
    }

    public function setFileSize($size)
    {
        $this->file_size = $size;
    }

    public function getDownloadLink()
    {
        return $this->download_link;
    }

    public function setDownloadLink($link)
    {
        $this->download_link = $link;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    public function getDimensions()
    {
        return $this->dimensions;
    }

    public function setDimensions($dim)
    {
        $this->dimensions = $dim;
    }

    // database operations 
    public function addProduct()
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products 
                (name, description, price, email, category_id, product_type, file_size, download_link, weight, dimensions, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $this->name,
            $this->description,
            $this->price,
            $this->email,
            $this->category_id,
            $this->product_type,
            $this->file_size,
            $this->download_link,
            $this->weight,
            $this->dimensions
        ]);
    }

    public function updateProduct()
    {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, email = ?, category_id = ?,
                product_type = ?, file_size = ?, download_link = ?, weight = ?, dimensions = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $this->name,
            $this->description,
            $this->price,
            $this->email,
            $this->category_id,
            $this->product_type,
            $this->file_size,
            $this->download_link,
            $this->weight,
            $this->dimensions,
            $this->id
        ]);
    }

    public function deleteProduct()
    {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public function getProduct($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->description = $data['description'];
            $this->price = $data['price'];
            $this->email = $data['email'];
            $this->category_id = $data['category_id'];
            $this->product_type = $data['product_type'];
            $this->file_size = $data['file_size'];
            $this->download_link = $data['download_link'];
            $this->weight = $data['weight'];
            $this->dimensions = $data['dimensions'];
        }

        return $data;
    }

    public function getAllProducts()
    {
        $stmt = $this->pdo->query("
        SELECT p.id, p.name, p.description, p.price, p.email, 
               p.type, p.created_at, 
               c.name AS category_name,
               p.file_size, p.download_link, 
               p.weight, p.dimensions
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
    ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
