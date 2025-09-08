<?php
require_once __DIR__ . '/../models/CartModel.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../helpers/Mailer.php';

class CartController
{
    protected $pdo;
    protected $cartModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->cartModel = new CartModel($pdo);
    }

    // show cart
    public function index()
    {
        // Determine cart ID: if logged in, fetch DB cart; otherwise use session cart
        if (isset($_SESSION['user_id'])) {
            $cartId = $this->cartModel->getCartIdForUser((int) $_SESSION['user_id']);
            $items = $cartId ? $this->cartModel->getCartItems($cartId) : [];
        } else {
            $sessionCart = $_SESSION['cart'] ?? [];
            // session items are [ ['product_id'=>x,'quantity'=>y], ... ]
            $items = [];
            foreach ($sessionCart as $it) {
                // fetch product details for each product_id
                $stmt = $this->pdo->prepare("
                        SELECT p.id AS product_id, p.name, p.price, p.type, c.name AS category_name
                        FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1
                    ");
                $stmt->execute([(int) $it['product_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['quantity'] = (int) $it['quantity'];
                    $row['line_total'] = number_format($row['quantity'] * (float) $row['price'], 2, '.', '');
                    $items[] = $row;
                }
            }
        }

        include __DIR__ . '/../views/cart/index.php';
    }

    // add to cart
    public function add()
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($productId <= 0) {
            header('Location: /');
            exit;
        }

        // If user logged in -> use DB cart
        if (isset($_SESSION['user_id'])) {
            $cartId = $this->cartModel->getOrCreateCartForUser((int) $_SESSION['user_id']);
            $this->cartModel->addItem($cartId, $productId, $quantity, false);
        } else {
            // Guest: use session cart (simple array of items)
            if (!isset($_SESSION['cart']))
                $_SESSION['cart'] = [];
            // find if exists
            $found = false;
            foreach ($_SESSION['cart'] as &$it) {
                if ((int) $it['product_id'] === $productId) {
                    $it['quantity'] = (int) $it['quantity'] + $quantity;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $_SESSION['cart'][] = ['product_id' => $productId, 'quantity' => $quantity];
            }
        }

        header('Location: /cart');
        exit;
    }

    // update quantity (POST: product_id, quantity)
    public function update()
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));

        if ($productId <= 0) {
            header('Location: /cart');
            exit;
        }

        if (isset($_SESSION['user_id'])) {
            $cartId = $this->cartModel->getOrCreateCartForUser((int) $_SESSION['user_id']);
            $this->cartModel->addItem($cartId, $productId, $quantity, true); // set quantity
        } else {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if ($quantity <= 0) {
                // Remove item (works even if it's the last one)
                $_SESSION['cart'] = array_values(array_filter(
                    $_SESSION['cart'],
                    fn($it) => (int) $it['product_id'] !== $productId
                ));
            } else {
                // Update quantity
                foreach ($_SESSION['cart'] as &$it) {
                    if ((int) $it['product_id'] === $productId) {
                        $it['quantity'] = $quantity;
                        break;
                    }
                }
                unset($it); // break reference
            }
        }

        header('Location: /cart');
        exit;
    }

    // remove item
    public function delete()
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            header('Location: /cart');
            exit;
        }

        if (isset($_SESSION['user_id'])) {
            $cartId = $this->cartModel->getCartIdForUser((int) $_SESSION['user_id']);
            if ($cartId) {
                $this->cartModel->removeItem($cartId, $productId);
            }
        } else {
            if (!isset($_SESSION['cart']))
                $_SESSION['cart'] = [];
            foreach ($_SESSION['cart'] as $k => $it) {
                if ((int) $it['product_id'] === $productId) {
                    unset($_SESSION['cart'][$k]);
                    break;
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }

        header('Location: /cart');
        exit;
    }

    public function checkout()
    {
        // Build items array like in index()
        if (isset($_SESSION['user_id'])) {
            $items = $this->cartModel->getCartItems($this->cartModel->getCartIdForUser((int) $_SESSION['user_id']) ?? 0);
            // normalize items to expected format (product_id, quantity, price)
            $cartItems = array_map(function ($r) {
                return ['product_id' => (int) $r['product_id'], 'quantity' => (int) $r['quantity'], 'price' => $r['price'], 'name' => $r['name']];
            }, $items);
        } else {
            $sessionCart = $_SESSION['cart'] ?? [];
            $cartItems = [];
            foreach ($sessionCart as $it) {
                $stmt = $this->pdo->prepare("SELECT id AS product_id, name, price, stock FROM products WHERE id = ? LIMIT 1");
                $stmt->execute([(int) $it['product_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['quantity'] = (int) $it['quantity'];
                    $cartItems[] = $row;
                }
            }
        }

        include __DIR__ . '/../views/cart/checkout.php';
    }

    // Place order (POST) — used for Cash-On-Delivery / immediate non-Stripe flows
    public function placeOrder()
    {
        $shippingAddress = trim($_POST['shipping_address'] ?? null);
        if (empty($shippingAddress)) {
            $_SESSION['checkout_error'] = 'Shipping address is required.';
            header('Location: /checkout');
            exit;
        }

        // Build items
        if (isset($_SESSION['user_id'])) {
            $ordersModel = new OrderModel($this->pdo);
            $items = $ordersModel->getItemsFromUserCart((int) $_SESSION['user_id']);
            $userId = (int) $_SESSION['user_id'];
        } else {
            $userId = null;
            $sessionCart = $_SESSION['cart'] ?? [];
            $items = array_map(function ($it) {
                return ['product_id' => (int) $it['product_id'], 'quantity' => (int) $it['quantity']];
            }, $sessionCart);
        }

        if (empty($items)) {
            $_SESSION['checkout_error'] = 'Your cart is empty.';
            header('Location: /cart');
            exit;
        }

        $ordersModel = new OrderModel($this->pdo);

        try {
            // 1) create pending order (same as Stripe flow)
            $res = $ordersModel->createPendingOrder($userId, $items, $shippingAddress);
            $orderId = (int) $res['order_id'];

            // 2) finalize immediately (deduct stock, mark paid) because this is COD / offline
            // pass a custom payment_status 'cod' or 'offline' so records are consistent
            $ordersModel->finalizeOrderPayment($orderId, null, 'cod');

            // 3) clear cart
            if (isset($_SESSION['user_id'])) {
                $cartId = $this->cartModel->getCartIdForUser((int) $_SESSION['user_id']);
                if ($cartId) {
                    $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                    $stmt->execute([$cartId]);
                }
            } else {
                unset($_SESSION['cart']);
            }

            header('Location: /order/confirmation?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $_SESSION['checkout_error'] = 'Failed to place order: ' . $e->getMessage();
            header('Location: /checkout');
            exit;
        }
    }

    // create Checkout Session (responds to JSON)
    public function createCheckoutSession()
    {
        // read JSON body (we use fetch from client)
        $input = json_decode(file_get_contents('php://input'), true);
        $shippingAddress = trim($input['shipping_address'] ?? '');

        if (empty($shippingAddress)) {
            http_response_code(400);
            echo json_encode(['error' => 'Shipping address is required']);
            return;
        }

        // build items as earlier
        if (isset($_SESSION['user_id'])) {
            $ordersModel = new OrderModel($this->pdo);
            $items = $ordersModel->getItemsFromUserCart((int) $_SESSION['user_id']);
            $userId = (int) $_SESSION['user_id'];
        } else {
            $userId = null;
            $sessionCart = $_SESSION['cart'] ?? [];
            $items = array_map(function ($it) {
                return ['product_id' => (int) $it['product_id'], 'quantity' => (int) $it['quantity']];
            }, $sessionCart);
        }

        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart is empty']);
            return;
        }

        // Create pending order (no stock changes yet)
        $ordersModel = new OrderModel($this->pdo);
        try {
            $res = $ordersModel->createPendingOrder($userId, $items, $shippingAddress);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
            return;
        }

        $orderId = (int) $res['order_id'];
        $total = (float) $res['total'];

        // Build Stripe line_items from the cart (server reads fresh product prices to avoid tampering)
        $line_items = [];
        $stmt = $this->pdo->prepare("SELECT id, name, price FROM products WHERE id = ? LIMIT 1");
        foreach ($items as $it) {
            $stmt->execute([(int) $it['product_id']]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$p) {
                http_response_code(500);
                echo json_encode(['error' => 'Product not found: ' . $it['product_id']]);
                return;
            }
            $unitAmountCents = (int) round((float) $p['price'] * 100);
            $line_items[] = [
                'price_data' => [
                    'currency' => 'inr', // adapt to your currency or pass dynamically
                    'product_data' => ['name' => $p['name']],
                    'unit_amount' => $unitAmountCents
                ],
                'quantity' => (int) $it['quantity']
            ];
        }

        // Create Stripe Checkout Session
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Build success/cancel URLs (absolute)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $success_url = "{$scheme}://{$host}/payment/success?session_id={CHECKOUT_SESSION_ID}";
        $cancel_url = "{$scheme}://{$host}/payment/cancel?order_id={$orderId}";

        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => $line_items,
                'metadata' => ['order_id' => (string) $orderId],
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
            ]);

            // Return the session id to the client
            header('Content-Type: application/json');
            echo json_encode(['id' => $checkout_session->id]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Stripe error: ' . $e->getMessage()]);
            return;
        }
    }

    /**
     * Success page (redirected back from Stripe)
     */
    public function paymentSuccess()
    {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            // No session id - send user back to checkout with message
            $_SESSION['checkout_error'] = 'Missing payment session. Please try again or contact support.';
            header('Location: /checkout');
            return;
        }

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? ($_ENV['STRIPE_KEY'] ?? ''));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            $orderId = isset($session->metadata->order_id) ? (int) $session->metadata->order_id : null;
            $paymentIntentId = $session->payment_intent ?? null;
            $paymentStatus = $session->payment_status ?? null;

            if (!$orderId) {
                $_SESSION['checkout_error'] = 'Order not found in payment session. Please contact support.';
                header('Location: /checkout');
                return;
            }

            if ($paymentStatus !== 'paid') {
                // Not paid — keep order pending and send user back to checkout or show message
                $_SESSION['checkout_error'] = 'Payment not completed (status: ' . htmlspecialchars($paymentStatus) . ').';
                header('Location: /checkout');
                return;
            }

            // finalize order (idempotent)
            $ordersModel = new OrderModel($this->pdo);
            try {
                $ordersModel->finalizeOrderPayment($orderId, $paymentIntentId, $paymentStatus);
            } catch (Exception $ex) {
                // finalization failed (stock issue, etc.)
                $_SESSION['checkout_error'] = 'Order finalization failed: ' . $ex->getMessage();
                header('Location: /checkout');
                return;
            }

            // clear cart after successful finalization
            if (isset($_SESSION['user_id'])) {
                $cartId = $this->cartModel->getCartIdForUser((int) $_SESSION['user_id']);
                if ($cartId) {
                    $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                    $stmt->execute([$cartId]);
                }
            } else {
                unset($_SESSION['cart']);
            }

            // optional: send order confirmation email here (mailer helper)
            $_SESSION['last_order_id'] = $orderId;


            // Call mailer only if we have a valid email
            $toEmail = $_SESSION['email'];
            $toName = $_SESSION['username'];
            Mailer::sendOrderConfirmation($toEmail, $toName);

            // Redirect to a proper confirmation page (recommended)
            include __DIR__ . '/../views/payment/success.php';

            // Mailer::sendOrderConfirmation($_SESSION['email'], $_SESSION['username']);
            return;

        } catch (Exception $e) {
            // Stripe retrieval failed
            $_SESSION['checkout_error'] = 'Could not verify payment with Stripe: ' . $e->getMessage();
            header('Location: /checkout');
            return;
        }
    }

    /**
     * Cancel page (customer cancelled on Stripe). Order remains pending_payment.
     */
    public function paymentCancel()
    {
        // You can show a page that displays the order id from query param
        // or instruct the user to retry.
        $orderId = $_GET['order_id'] ?? null;
        $_SESSION['last_cancelled_order'] = $orderId;
        include __DIR__ . '/../views/payment/cancel.php';
    }

}
