<?php

require_once __DIR__ . '/PhysicalProductModel.php';
require_once __DIR__ . '/DigitalProductModel.php';

class ProductModel
{
    private $id;
    private $name;
    private $price;
    private $type;
    private $category_id;

    protected $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    //getters and setters 
    public function getId(){ return $this->id; }
    public function setId($id){ return $this->id = $id; }

    public function getName()    { return $this->name; }
    public function setName($name)    { return $this->name = $name; }

    public function getPrice() { return $this->price; }
    public function setPrice($price){ return $this->price = $price; }

    public function getType(){ return $this->type; }
    public function setType($type){ return $this->type = $type; }

    public function getCategoryId(){ return $this->category_id; }
    public function setCategoryId($category_id){ return $this->category_id = $category_id; }


    // Data Access Methods


    // Return all Products
    public function all(): array
    {
        $sql = "
        SELECT p.id, p.name, p.price, p.type, p.category_id, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Return categories list
    public function categories(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Save product details to the DB
    public function create(array $data): int
    {
        try {
            $this->pdo->beginTransaction();

            $insertProduct = $this->pdo->prepare("
                INSERT INTO products (name, price, type, category_id)
                VALUES (:name, :price, :type, :category_id)
            ");
            $insertProduct->execute([
                ':name' => $data['name'],
                ':price' => $data['price'],
                ':type' => $data['type'],
                ':category_id' => $data['category_id']
            ]);

            $productId = $this->pdo->lastInsertId();

            if ($data['type'] === 'physical') {
                $insertPhysical = $this->pdo->prepare("
                    INSERT INTO physical_product (product_id, weight, dimensions)
                    VALUES (:product_id, :weight, :dimensions)
                ");
                $insertPhysical->execute([
                    ':product_id' => $productId,
                    ':weight' => $data['weight'],
                    ':dimensions' => $data['dimensions']
                ]);
            } else {
                $insertDigital = $this->pdo->prepare("
                    INSERT INTO digital_product (product_id, file_size, download_url)
                    VALUES (:product_id, :file_size, :download_url)
                ");
                $insertDigital->execute([
                    ':product_id' => $productId,
                    ':file_size' => $data['file_size'],
                    ':download_url' => $data['download_url']
                ]);
            }

            $this->pdo->commit();

            return (int)$productId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Find product by id
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch subtype data depending on type
    public function getSubtype(int $productId, string $type): array
    {
        if ($type === 'physical') {
            $s = $this->pdo->prepare("SELECT weight, dimensions FROM physical_product WHERE product_id = ? LIMIT 1");
            $s->execute([$productId]);
            $sub = $s->fetch(PDO::FETCH_ASSOC);
            return $sub ? $sub : [];
        } else {
            $s = $this->pdo->prepare("SELECT file_size, download_url FROM digital_product WHERE product_id = ? LIMIT 1");
            $s->execute([$productId]);
            $sub = $s->fetch(PDO::FETCH_ASSOC);
            return $sub ? $sub : [];
        }
    }

    // Update the database according to the data.
    public function update(int $id, array $data): void
    {
        try {
            $this->pdo->beginTransaction();

            $update = $this->pdo->prepare("UPDATE products SET name = ?, price = ?, type = ?, category_id = ? WHERE id = ?");
            $update->execute([$data['name'], $data['price'], $data['type'], $data['category_id'], $id]);

            // Remove any existing subtype rows
            $delP = $this->pdo->prepare("DELETE FROM physical_product WHERE product_id = ?");
            $delP->execute([$id]);

            $delD = $this->pdo->prepare("DELETE FROM digital_product WHERE product_id = ?");
            $delD->execute([$id]);

            // Insert new subtype row
            if ($data['type'] === 'physical') {
                $insertP = $this->pdo->prepare("INSERT INTO physical_product (product_id, weight, dimensions) VALUES (?, ?, ?)");
                $insertP->execute([$id, $data['weight'], $data['dimensions']]);
            } else {
                $insertD = $this->pdo->prepare("INSERT INTO digital_product (product_id, file_size, download_url) VALUES (?, ?, ?)");
                $insertD->execute([$id, $data['file_size'], $data['download_url']]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Delete product and its subtype rows
    public function delete(int $id): void
    {
        try {
            $this->pdo->beginTransaction();

            $delPhysical = $this->pdo->prepare("DELETE FROM physical_product WHERE product_id = ?");
            $delPhysical->execute([$id]);

            $delDigital = $this->pdo->prepare("DELETE FROM digital_product WHERE product_id = ?");
            $delDigital->execute([$id]);

            $delProduct = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $delProduct->execute([$id]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
