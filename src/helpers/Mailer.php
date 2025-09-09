<?php
// src/helpers/Mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/PHPMailer/Exception.php';
require __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require __DIR__ . '/../../vendor/PHPMailer/SMTP.php';

// Use composer autoload (robust)
require_once __DIR__ . '/../../vendor/autoload.php';

class Mailer
{
    public static function sendOrderConfirmation(string $toEmail, string $toName, $order)
    {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('Mailer: invalid or empty recipient email: ' . var_export($toEmail, true));
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host = $_ENV['SMTP_HOST'];                       //Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   //Enable SMTP authentication
            $mail->Username = $_ENV['SMTP_USERNAME'];                       //SMTP username
            $mail->Password = $_ENV['SMTP_PASSWORD'];                                //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('sambitsarkardbpc@gmail.com', 'Payment');
            $mail->addAddress($toEmail, $toName);     //Add a recipient

            // Content
            // Content
            $mail->isHTML(true);

            // Determine order details:
// $orderParam may be an order id (int/string) or an array/object returned by OrderModel::findOrderWithItems.
// We'll try to resolve it to an associative array $orderData with keys like ['id', 'total_amount', 'shipping_address', 'items' => [...]]
            $orderData = [];

            // If numeric, treat as order id and fetch using OrderModel
            if (is_numeric($order)) {
                $orderId = (int) $order;
                // Try to obtain PDO from global scope
                $pdo = $GLOBALS['pdo'] ?? null;
                if ($pdo instanceof PDO) {
                    // load OrderModel and fetch order with items
                    require_once __DIR__ . '/../models/OrderModel.php';
                    $orderModel = new OrderModel($pdo);
                    $orderData = $orderModel->findOrderWithItems($orderId);
                }
            } elseif (is_array($order)) {
                $orderData = $order;
            } elseif (is_object($order)) {
                // If an OrderModel instance or object-like, try to convert
                if (method_exists($order, 'findOrderWithItems')) {
                    // user passed an OrderModel instance — not typical, but handle gracefully
                    // assume they want order id in $order->id
                    $oid = $order->id ?? null;
                    if ($oid && ($GLOBALS['pdo'] ?? null) instanceof PDO) {
                        require_once __DIR__ . '/../models/OrderModel.php';
                        $om = new OrderModel($GLOBALS['pdo']);
                        $orderData = $om->findOrderWithItems((int) $oid);
                    }
                } else {
                    // try to cast object to array
                    $orderData = (array) $order;
                }
            }

            // Fallback when order data not available
            $orderIdDisplay = isset($orderData['id']) ? (int) $orderData['id'] : (is_numeric($order) ? (int) $order : 'N/A');
            $shipping = $orderData['shipping_address'] ?? 'Not provided';
            $grandTotal = isset($orderData['total_amount']) ? number_format((float) $orderData['total_amount'], 2, '.', '') : null;

            // Build items HTML
            $itemsHtml = '';
            if (!empty($orderData['items']) && is_array($orderData['items'])) {
                foreach ($orderData['items'] as $item) {
                    // Item fields in your OrderModel->findOrderWithItems() are: product_id, quantity, unit_price, line_total, name (aliased as p.name)
                    $pname = htmlspecialchars($item['name'] ?? ('Product #' . ($item['product_id'] ?? '')));
                    $qty = (int) ($item['quantity'] ?? 0);
                    // Prefer line_total if present, otherwise compute from unit_price * qty
                    $line = isset($item['line_total'])
                        ? number_format((float) $item['line_total'], 2, '.', '')
                        : number_format(((float) ($item['unit_price'] ?? 0) * $qty), 2, '.', '');
                    $itemsHtml .= "<li>{$pname} (x{$qty}) — ₹ {$line}</li>";
                }
            } else {
                $itemsHtml = '<li>No items found for this order.</li>';
            }

            // Subject
            $mail->Subject = "Order #{$orderIdDisplay} Confirmation";

            // Body (HTML)
            $toNameEsc = htmlspecialchars($toName);
            $shippingEsc = nl2br(htmlspecialchars($shipping));

            $grandHtml = $grandTotal !== null ? "<p><strong>Grand Total:</strong> ₹ {$grandTotal}</p>" : "";

            $mail->Body = "
            <div style=\"font-family:Arial, sans-serif; line-height:1.4; color:#333;\">
            <h2 style=\"color:#2b7bb9;\">Thank you for your order, {$toNameEsc}!</h2>
            <p>Your order <strong>#{$orderIdDisplay}</strong> has been received and is being processed.</p>

            <h4>Shipping Address</h4>
            <p>{$shippingEsc}</p>

            <h4>Order Details</h4>
            <ul style=\"margin-left:1rem;\">{$itemsHtml}</ul>

            {$grandHtml}

            <p>If you have any questions, reply to this email and we'll help you out.</p>

            <hr>
            <p style=\"font-size:0.85rem;color:#666;\">This is an automated message — please do not reply to this address directly.</p>
            </div>
";


            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
