<?php
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $this->pdo = $GLOBALS['pdo'];
    }

    public function testOrderProcessing()
    {
        $stmt = $this->pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, ?)");
        $result = $stmt->execute([1, 250.00, 'paid']);
        $this->assertTrue($result);

        $order = $this->pdo->query("SELECT * FROM orders WHERE user_id = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('paid', $order['status']);
    }
}
