<?php
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $this->pdo = $GLOBALS['pdo'];
    }

    public function testUserRegistration()
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute(['testuser', 'test@example.com', password_hash('password', PASSWORD_DEFAULT), 'customer']);
        $this->assertTrue($result);

        $user = $this->pdo->query("SELECT * FROM users WHERE username = 'testuser'")->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('testuser', $user['username']);
    }
}
