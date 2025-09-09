<?php
require_once __DIR__ . '/../models/UserModel.php';
class UserRepository
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new UserModel($data['id'], $data['username'], $data['email'], $data['password_hash'], $data['role']) : null;
    }

    public function findByUsername(string $username): ?UserModel
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new UserModel($data['id'], $data['username'], $data['email'], $data['password_hash'], $data['role']) : null;
    }

    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(UserModel $user): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getRole()
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}