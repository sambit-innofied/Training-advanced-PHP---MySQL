<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Payment cancelled</title></head>
<body class="container py-4">
  <h1>Payment cancelled</h1>
  <?php if (!empty($_SESSION['last_cancelled_order'])): ?>
    <p>Order #<?= htmlspecialchars($_SESSION['last_cancelled_order']) ?> was not paid.</p>
    <?php unset($_SESSION['last_cancelled_order']); ?>
  <?php endif; ?>
  <a href="/cart" class="btn btn-secondary">Back to cart</a>
</body>
</html>
