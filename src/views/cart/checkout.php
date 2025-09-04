<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Checkout</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="container py-4">
  <h2>Checkout</h2>

  <?php if (!empty($_SESSION['checkout_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['checkout_error']) ?></div>
    <?php unset($_SESSION['checkout_error']); ?>
  <?php endif; ?>

  <?php if (empty($cartItems)): ?>
    <div class="alert alert-info">Your cart is empty.</div>
    <a href="/" class="btn btn-primary">Continue shopping</a>
  <?php else: ?>
    <h4>Items</h4>
    <ul class="list-group mb-3">
      <?php $grand = 0;
      foreach ($cartItems as $it):
        $line = number_format(((float) $it['price'] ?? 0) * ($it['quantity']), 2, '.', '');
        $grand += (float) $line; ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= htmlspecialchars($it['name'] ?? 'Product #' . $it['product_id']) ?>
          <span><?= htmlspecialchars($it['quantity']) ?> × <?= htmlspecialchars($it['price'] ?? '0.00') ?> =
            <?= htmlspecialchars($line) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="mb-3"><strong>Grand total:</strong> <?= number_format($grand, 2, '.', '') ?></div>

    <form id="checkoutForm">
      <div class="mb-3">
        <label class="form-label">Shipping address</label>
        <textarea id="shipping_address" name="shipping_address" class="form-control"
          required><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>
      </div>

      <button class="btn btn-success" id="placeOrderBtn" type="button">Pay with Card</button>
      <a class="btn btn-secondary" href="/cart">Back to cart</a>
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
      const stripe = Stripe("<?= htmlspecialchars($_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '') ?>");

      document.getElementById('placeOrderBtn').addEventListener('click', async function () {
        const shipping_address = document.getElementById('shipping_address').value.trim();
        if (!shipping_address) {
          alert('Please enter shipping address.');
          return;
        }

        try {
          const resp = await fetch('/create-checkout-session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ shipping_address })
          });

          const data = await resp.json();
          if (resp.ok && data.id) {
            // redirect to Stripe Checkout
            const { error } = await stripe.redirectToCheckout({ sessionId: data.id });
            if (error) {
              alert(error.message || 'Stripe redirect failed');
            }
          } else {
            alert(data.error || 'Failed to create checkout session');
          }
        } catch (err) {
          alert('Network error: ' + err.message);
        }
      });
    </script>

  <?php endif; ?>
</body>

</html>