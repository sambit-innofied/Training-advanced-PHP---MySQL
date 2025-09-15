<?php
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../CartModel.php';

class OrderRepository
{
    protected PDO $pdo;
    protected CartRepository $cartRepository;
    protected string $productTable = 'products';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->cartRepository = new CartRepository($pdo);
    }
    public function createPendingOrder(?int $userId, array $items, ?string $shippingAddress = null): array
    {
        if (empty($items)) {
            throw new Exception("Cart is empty");
        }

        $this->pdo->beginTransaction();
        try {
            $total = 0.0;
            $stmtProduct = $this->pdo->prepare("SELECT id, price FROM {$this->productTable} WHERE id = ? LIMIT 1");

            $orderItemsToInsert = [];
            foreach ($items as $it) {
                $productId = (int) $it['product_id'];
                $qty = max(0, (int) $it['quantity']);
                if ($qty <= 0) {
                    throw new Exception("Invalid quantity for product {$productId}");
                }

                $stmtProduct->execute([$productId]);
                $prod = $stmtProduct->fetch(PDO::FETCH_ASSOC);
                if (!$prod) {
                    throw new Exception("Product {$productId} not found");
                }

                $unitPrice = number_format((float) $prod['price'], 2, '.', '');
                $lineTotal = number_format($unitPrice * $qty, 2, '.', '');
                $total += (float) $lineTotal;

                $orderItemsToInsert[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal
                ];
            }

            $stmtInsertOrder = $this->pdo->prepare("
                INSERT INTO orders (user_id, total_amount, status, shipping_address)
                VALUES (?, ?, ?, ?)
            ");
            $status = 'pending_payment';
            $stmtInsertOrder->execute([$userId, number_format($total, 2, '.', ''), $status, $shippingAddress]);

            $orderId = (int) $this->pdo->lastInsertId();

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

    public function finalizeOrderPayment(int $orderId, ?string $paymentIntentId = null, ?string $paymentStatus = null): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmtOrder = $this->pdo->prepare("SELECT id, status FROM orders WHERE id = ? LIMIT 1");
            $stmtOrder->execute([$orderId]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception("Order {$orderId} not found");
            }

            if (($order['status'] ?? '') === 'paid') {
                $this->pdo->commit();
                return;
            }

            $stmtItems = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$orderId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                throw new Exception("Order {$orderId} has no items");
            }

            $stmtLock = $this->pdo->prepare("SELECT id, stock FROM {$this->productTable} WHERE id = ? FOR UPDATE");
            $stmtUpdateStock = $this->pdo->prepare("UPDATE {$this->productTable} SET stock = stock - ? WHERE id = ?");

            foreach ($items as $it) {
                $pid = (int) $it['product_id'];
                $qty = (int) $it['quantity'];

                $stmtLock->execute([$pid]);
                $prod = $stmtLock->fetch(PDO::FETCH_ASSOC);

                if (!$prod) {
                    throw new Exception("Product {$pid} not found while finalizing order {$orderId}");
                }

                if ((int) $prod['stock'] < $qty) {
                    throw new Exception("Insufficient stock for product {$pid} while finalizing order {$orderId}");
                }

                $stmtUpdateStock->execute([$qty, $pid]);
            }

            $stmtUpdateOrder = $this->pdo->prepare("UPDATE orders SET status = ?, payment_intent_id = ?, payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtUpdateOrder->execute(['paid', $paymentIntentId, $paymentStatus, $orderId]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getItemsFromUserCart(int $userId): array
    {
        $cartId = $this->cartRepository->getCartIdForUser($userId);
        if (!$cartId)
            return [];

        return $this->cartRepository->getCartItems($cartId);
    }

    public function findOrderWithItems(int $orderId): array
    {
        $stmtOrder = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$order)
            return [];

        $stmtItems = $this->pdo->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        $stmtItems->execute([$orderId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $order['items'] = $items;
        return $order;
    }
}
