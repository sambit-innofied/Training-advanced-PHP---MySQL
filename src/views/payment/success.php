<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Payment success</title></head>
<body class="container py-4">
  <h1>Payment Successful</h1>
  <?php if (!empty($_SESSION['last_order_id'])): ?>
    <p>Order #<?= htmlspecialchars($_SESSION['last_order_id']) ?> placed. Thank you!</p>
    <?php unset($_SESSION['last_order_id']); ?>
  <?php else: ?>
    <p>Your payment completed. Check your orders for details.</p>
  <?php endif; ?>
  <a href="/" class="btn btn-primary">Continue shopping</a>
</body>
</html>
