<?php

class CartModel
{
    private ?int $id;
    private ?int $user_id;

    public function __construct(?int $id = null, ?int $user_id = null)
    {
        $this->id = $id;
        $this->user_id = $user_id;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }

    public function getUserId(): ?int { return $this->user_id; }
    public function setUserId(?int $user_id): self { $this->user_id = $user_id; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id
        ];
    }
}
