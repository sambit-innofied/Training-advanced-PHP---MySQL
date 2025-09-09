<?php

require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/PhysicalProductModel.php';
require_once __DIR__ . '/../models/DigitalProductModel.php';

class ProductRepository
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $sql = "
            SELECT p.id, p.name, p.price, p.type, p.category_id, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.id DESC
        ";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->mapRowToEntity($row);
        }
        return $result;
    }

    public function categories(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            return null;

        return $this->mapRowToEntity($row);
    }

    public function create($product): int
    {
        $data = [
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'type' => $product->getType(),
            'category_id' => $product->getCategoryId()
        ];

        if ($product instanceof PhysicalProductModel) {
            $data['type'] = 'physical';
            $data['weight'] = $product->getWeight();
            $data['dimensions'] = $product->getDimensions();
        } elseif ($product instanceof DigitalProductModel) {
            $data['type'] = 'digital';
            $data['file_size'] = $product->getFileSize();
            $data['download_url'] = $product->getDownloadUrl();
        }

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

            $productId = (int) $this->pdo->lastInsertId();

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
                    ':file_size' => $data['file_size'] ?? null,
                    ':download_url' => $data['download_url'] ?? null
                ]);
            }

            $this->pdo->commit();
            return $productId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update($product): void
    {
        $id = $product->getId();
        if (!$id) {
            throw new InvalidArgumentException('Product id is required for update');
        }

        $data = [
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'type' => $product->getType(),
            'category_id' => $product->getCategoryId()
        ];

        if ($product instanceof PhysicalProductModel) {
            $data['type'] = 'physical';
            $data['weight'] = $product->getWeight();
            $data['dimensions'] = $product->getDimensions();
        } elseif ($product instanceof DigitalProductModel) {
            $data['type'] = 'digital';
            $data['file_size'] = $product->getFileSize();
            $data['download_url'] = $product->getDownloadUrl();
        }

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
                $insertD->execute([$id, $data['file_size'] ?? null, $data['download_url'] ?? null]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

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

    protected function mapRowToEntity(array $row)
    {
        $sub = $this->getSubtype((int) $row['id'], $row['type']);

        if ($row['type'] === 'physical') {
            $entity = new PhysicalProductModel((int) $row['id'], $row['name'], $row['price'], $row['type'], $row['category_id'] ?? null);
            $entity->setWeight($sub['weight'] ?? null);
            $entity->setDimensions($sub['dimensions'] ?? null);
        } else {
            $entity = new DigitalProductModel((int) $row['id'], $row['name'], $row['price'], $row['type'], $row['category_id'] ?? null);
            $entity->setFileSize($sub['file_size'] ?? null);
            $entity->setDownloadUrl($sub['download_url'] ?? null);
        }

        $entity->setCategoryName($row['category_name'] ?? null);

        return $entity;
    }
}
