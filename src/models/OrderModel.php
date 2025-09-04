<?php
// src/models/OrdersModel.php

class OrderModel
{
    protected $pdo;
    protected $cartModel;
    protected $productTable = 'products';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/CartModel.php';
        $this->cartModel = new CartModel($pdo);
    }

    /**
     * Create a "pending" order and order_items (does NOT touch stock).
     * Returns array: ['order_id' => int, 'total' => float]
     */
    public function createPendingOrder(?int $userId, array $items, ?string $shippingAddress = null): array
    {
        if (empty($items)) {
            throw new Exception("Cart is empty");
        }

        $this->pdo->beginTransaction();
        try {
            $total = 0.0;

            // insert order row
            $stmtInsertOrder = $this->pdo->prepare("
                INSERT INTO orders (user_id, total_amount, status, shipping_address)
                VALUES (?, ?, ?, ?)
            ");
            $status = 'pending_payment';
            // compute total and prepare order_items insertion after we fetch unit prices
            $stmtProduct = $this->pdo->prepare("SELECT id, price FROM {$this->productTable} WHERE id = ? LIMIT 1");

            $orderItemsToInsert = [];
            foreach ($items as $it) {
                $productId = (int)$it['product_id'];
                $qty = max(0, (int)$it['quantity']);
                if ($qty <= 0) {
                    throw new Exception("Invalid quantity for product {$productId}");
                }

                $stmtProduct->execute([$productId]);
                $prod = $stmtProduct->fetch(PDO::FETCH_ASSOC);
                if (!$prod) {
                    throw new Exception("Product {$productId} not found");
                }

                $unitPrice = number_format((float)$prod['price'], 2, '.', '');
                $lineTotal = number_format($unitPrice * $qty, 2, '.', '');
                $total += (float)$lineTotal;

                $orderItemsToInsert[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal
                ];
            }

            $stmtInsertOrder->execute([$userId, number_format($total, 2, '.', ''), $status, $shippingAddress]);
            $orderId = (int)$this->pdo->lastInsertId();

            // insert order_items
            $stmtInsertItem = $this->pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)");
            foreach ($orderItemsToInsert as $it) {
                $stmtInsertItem->execute([$orderId, $it['product_id'], $it['quantity'], $it['unit_price'], $it['line_total']]);
            }

            $this->pdo->commit();
            return ['order_id' => $orderId, 'total' => $total];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Finalize an order after successful payment:
     * - Validates and deducts stock (inside a transaction with FOR UPDATE)
     * - Updates order status to 'paid' and saves payment intent id/status
     * Safe to call multiple times (idempotent).
     */
    public function finalizeOrderPayment(int $orderId, ?string $paymentIntentId = null, ?string $paymentStatus = null): void
    {
        $this->pdo->beginTransaction();
        try {
            // fetch order
            $s = $this->pdo->prepare("SELECT id, status FROM orders WHERE id = ? LIMIT 1");
            $s->execute([$orderId]);
            $order = $s->fetch(PDO::FETCH_ASSOC);
            if (!$order) {
                throw new Exception("Order {$orderId} not found");
            }

            // if already paid, nothing to do
            if (($order['status'] ?? '') === 'paid') {
                $this->pdo->commit();
                return;
            }

            // fetch order_items
            $stmtItems = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$orderId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                throw new Exception("Order {$orderId} has no items");
            }

            // Lock and validate each product row, then deduct stock
            $stmtLock = $this->pdo->prepare("SELECT id, stock FROM {$this->productTable} WHERE id = ? FOR UPDATE");
            $stmtUpdateStock = $this->pdo->prepare("UPDATE {$this->productTable} SET stock = stock - ? WHERE id = ?");

            foreach ($items as $it) {
                $pid = (int)$it['product_id'];
                $qty = (int)$it['quantity'];

                $stmtLock->execute([$pid]);
                $prod = $stmtLock->fetch(PDO::FETCH_ASSOC);

                if (!$prod) {
                    throw new Exception("Product {$pid} not found while finalizing order {$orderId}");
                }

                if ((int)$prod['stock'] < $qty) {
                    // Not enough stock — throw so the transaction rolls back.
                    throw new Exception("Insufficient stock for product {$pid} while finalizing order {$orderId}");
                }
                $stmtUpdateStock->execute([$qty, $pid]);
            }

            // Mark order as paid and save payment metadata
            $upd = $this->pdo->prepare("UPDATE orders SET status = ?, payment_intent_id = ?, payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute(['paid', $paymentIntentId, $paymentStatus, $orderId]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Helper for logged-in users: build items array from DB cart.
     * Returns items in the format expected by createPendingOrder.
     */
    public function getItemsFromUserCart(int $userId): array
    {
        $cartId = $this->cartModel->getCartIdForUser($userId);
        if (!$cartId) return [];
        // CartModel::getCartItems returns price, name etc. We transform into ['product_id','quantity']
        return array_map(function($row) {
            return ['product_id' => (int)$row['product_id'], 'quantity' => (int)$row['quantity']];
        }, $this->cartModel->getCartItems($cartId));
    }

    /**
     * Convenience: fetch order by id (with items)
     */
    public function findOrderWithItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) return [];

        $stmt = $this->pdo->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $order['items'] = $items;
        return $order;
    }
}
