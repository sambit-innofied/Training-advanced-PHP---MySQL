<?php

class UserModel
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find a user by username
     * Returns associative array (id, username, password_hash, role) or false if not found
     */
    public function findByUsername(string $username)
    {
        $stmt = $this->pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user by ID
     * Returns associative array or false if not found
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether a user exists by username or email.
     * Returns true if a user exists with either the given username or email.
     */
    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (bool)$row;
    }

    /**
     * Create a new user.
     * Returns the new user id (string/int) on success.
     * Throws exception on failure (let caller handle it).
     */
    public function create(string $username, string $email, string $password_hash, string $role = 'customer')
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash, $role]);
        return $this->pdo->lastInsertId();
    }
}
