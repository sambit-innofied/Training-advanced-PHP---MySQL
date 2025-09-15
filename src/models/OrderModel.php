<?php
class OrderModel
{
    public ?int $id;
    public ?int $userId;
    public float $totalAmount;
    public string $status;
    public ?string $shippingAddress;
    public ?string $paymentIntentId;
    public ?string $paymentStatus;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(?int $id, ?int $userId, float $totalAmount, string $status, ?string $shippingAddress)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->totalAmount = $totalAmount;
        $this->status = $status;
        $this->shippingAddress = $shippingAddress;
        $this->paymentIntentId = null;
        $this->paymentStatus = null;
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'shipping_address' => $this->shippingAddress,
            'payment_intent_id' => $this->paymentIntentId,
            'payment_status' => $this->paymentStatus,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
