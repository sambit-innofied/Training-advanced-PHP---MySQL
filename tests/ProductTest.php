<?php
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $this->pdo = $GLOBALS['pdo'];
    }

    public function testProductCreation()
    {
        $stmt = $this->pdo->prepare("INSERT INTO products (name, price, type) VALUES (?, ?, ?)");
        $result = $stmt->execute(['Test Product', 100, 'digital']);
        $this->assertTrue($result);

        $id = $this->pdo->lastInsertId();
        $product = $this->pdo->query("SELECT * FROM products WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Test Product', $product['name']);
    }
}
