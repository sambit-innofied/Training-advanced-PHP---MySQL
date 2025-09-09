<?php

class UserModel
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $role;

    public function __construct(?int $id, string $username, string $email, string $passwordHash, string $role = 'customer')
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): void { $this->username = $username; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $passwordHash): void { $this->passwordHash = $passwordHash; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): void { $this->role = $role; }
}
