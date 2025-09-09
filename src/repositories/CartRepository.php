<?php

require_once __DIR__ . '/../models/CartModel.php';

class CartRepository
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getOrCreateCartForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return (int)$row['id'];
        }

        $stmt = $this->pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->execute([$userId]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createGuestCart(): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO carts (user_id) VALUES (NULL)");
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    public function addItem(int $cartId, int $productId, int $quantity = 1, bool $setQuantity = false): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
            $stmt->execute([$cartId, $productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $newQty = $setQuantity ? max(0, $quantity) : ((int)$row['quantity'] + $quantity);

                if ($newQty <= 0) {
                    $del = $this->pdo->prepare("DELETE FROM cart_items WHERE id = ?");
                    $del->execute([$row['id']]);
                } else {
                    $upd = $this->pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $upd->execute([$newQty, $row['id']]);
                }
            } else {
                if ($quantity > 0) {
                    $ins = $this->pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                    $ins->execute([$cartId, $productId, $quantity]);
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function removeItem(int $cartId, int $productId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $productId]);
    }

    public function getCartItems(int $cartId): array
    {
        $sql = "
            SELECT ci.product_id, ci.quantity,
                   p.name, p.price, p.type, c.name AS category_name
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE ci.cart_id = ?
            ORDER BY ci.id DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cartId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['price'] = number_format((float)$r['price'], 2, '.', '');
            $r['quantity'] = (int)$r['quantity'];
            $r['line_total'] = number_format($r['quantity'] * (float)$r['price'], 2, '.', '');
        }

        return $rows;
    }

    public function getCartIdForUser(int $userId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    public function mergeSessionCartIntoUserCart(int $userId, array $sessionItems): void
    {
        if (empty($sessionItems)) return;

        $cartId = $this->getOrCreateCartForUser($userId);

        $this->pdo->beginTransaction();
        try {
            foreach ($sessionItems as $item) {
                $productId = (int)$item['product_id'];
                $qty = max(0, (int)$item['quantity']);
                if ($qty <= 0) continue;
                $this->addItem($cartId, $productId, $qty, false);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
