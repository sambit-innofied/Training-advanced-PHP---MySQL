<?php
// src/helpers/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// load composer autoload (adjust if your bootstrap loads it elsewhere)
require_once __DIR__ . '/../../vendor/autoload.php';

// Ensure OrdersModel is available. Adjust filename/classname if you use a different name.
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Send order confirmation email.
 *
 * @param PDO $pdo
 * @param int $orderId
 * @param string|null $toEmail  recipient email (if null, will try logged-in user email)
 * @param string|null $toName   optional recipient name
 * @return bool true on success, false on failure
 */
function sendOrderConfirmation(PDO $pdo, int $orderId, ?string $toEmail = null, ?string $toName = null): bool
{
    // Load environment variables (Dotenv should have run earlier in app bootstrap)
    print_r("hello working");
    $host = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST');
    $user = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER');
    $pass = $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS');
    $port = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 465);
    $secure = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?? 'ssl';
    $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?? 'no-reply@example.com';
    $fromName = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?? 'Store';
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?? null;

    // Basic validation
    if (empty($host) || empty($user) || empty($pass)) {
        error_log("Mailer: SMTP not configured properly.");
        return false;
    }

    // Fetch the order and items
    $ordersModel = new OrderModel($pdo);
    $order = $ordersModel->findOrderWithItems($orderId);
    if (empty($order)) {
        error_log("Mailer: Order {$orderId} not found.");
        return false;
    }

    // If recipient not provided, try to lookup user email from order.user_id
    if (empty($toEmail)) {
        $toEmail = null;
        $toName = null;
        if (!empty($order['user_id'])) {
            $userModel = new UserModel($pdo);
            $u = $userModel->findById((int)$order['user_id']);
            if ($u) {
                $toEmail = $u['email'] ?? null;
                $toName = $u['username'] ?? null;
            }
        }
    }

    // If still no recipient, try admin fallback
    if (empty($toEmail)) {
        if ($adminEmail) {
            $toEmail = $adminEmail;
            $toName = 'Admin';
        } else {
            error_log("Mailer: No recipient email available for order {$orderId}.");
            return false;
        }
    }

    // Build HTML body
    $subject = "Order Confirmation — Order #{$orderId}";
    $body = "<h2>Thank you for your order</h2>";
    $body .= "<p>Order ID: <strong>#{$orderId}</strong></p>";
    $body .= "<p>Status: <strong>" . htmlspecialchars($order['status'] ?? '') . "</strong></p>";
    if (!empty($order['shipping_address'])) {
        $body .= "<p><strong>Shipping address:</strong><br>" . nl2br(htmlspecialchars($order['shipping_address'])) . "</p>";
    }

    $body .= "<h4>Items</h4>";
    $body .= "<table style='width:100%; border-collapse:collapse;' border='1' cellpadding='6'>";
    $body .= "<thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead><tbody>";

    $i = 1;
    $grand = 0.0;
    foreach ($order['items'] as $it) {
        $line = number_format((float)$it['line_total'], 2, '.', '');
        $body .= "<tr>";
        $body .= "<td>{$i}</td>";
        $body .= "<td>" . htmlspecialchars($it['name'] ?? 'Product') . "</td>";
        $body .= "<td style='text-align:center;'>" . (int)$it['quantity'] . "</td>";
        $body .= "<td style='text-align:right;'>₹ " . number_format((float)$it['unit_price'], 2, '.', '') . "</td>";
        $body .= "<td style='text-align:right;'>₹ {$line}</td>";
        $body .= "</tr>";
        $i++;
        $grand += (float)$it['line_total'];
    }
    $body .= "</tbody></table>";

    $body .= "<p style='text-align:right; font-weight:bold; margin-top:10px;'>Grand total: ₹ " . number_format($grand, 2, '.', '') . "</p>";

    $body .= "<p>If you have any questions, reply to this email.</p>";
    $body .= "<hr><p style='font-size:12px;color:#666;'>This is an automated message.</p>";

    // Send via PHPMailer
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = ($secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS);
        $mail->Port       = $port;

        //Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName ?? '');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // send
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer: could not send order {$orderId} to {$toEmail}. Error: " . $mail->ErrorInfo);
        return false;
    }
}
