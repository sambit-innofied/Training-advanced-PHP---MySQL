<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Cart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    /* Small visual tweaks */
    .cart-card { max-width: 1000px; margin: 0 auto; }
    .qty-input { width: 96px; }
    .product-name { min-width: 220px; }
    .line-total { white-space: nowrap; }
  </style>
</head>
<body class="bg-light">

  <div class="container py-5">
    <div class="cart-card card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h3 class="card-title mb-0">Your Cart</h3>
          <div>
            <a href="/" class="btn btn-outline-secondary btn-sm">Continue shopping</a>
            <a href="/checkout" class="btn btn-success btn-sm ms-2">Checkout</a>
          </div>
        </div>

        <?php if (empty($items)): ?>
          <div class="text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" fill="currentColor" class="bi bi-cart3 mb-3 text-muted" viewBox="0 0 16 16">
              <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 13H4a.5.5 0 0 1-.49-.402L1.61 2H.5a.5.5 0 0 1-.5-.5zM4.14 6l1.25 6h7.22l1.25-6H4.14z"/>
            </svg>
            <h5 class="mt-3">Your cart is empty</h5>
            <p class="text-muted">Looks like you haven't added any products yet.</p>
            <a href="/" class="btn btn-primary">Start shopping</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle table-hover">
              <thead class="table-light">
                <tr>
                  <th class="product-name">Product</th>
                  <th class="text-end">Price</th>
                  <th style="width: 240px;">Qty</th>
                  <th class="text-end">Line total</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $grand = 0; foreach ($items as $it): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="ms-2">
                          <div class="fw-semibold"><?= htmlspecialchars($it['name']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($it['type'] ?? '') ?> <?= isset($it['category_name']) ? ' • ' . htmlspecialchars($it['category_name']) : '' ?></div>
                        </div>
                      </div>
                    </td>

                    <td class="text-end">₹ <?= htmlspecialchars(number_format((float)$it['price'],2,'.','')) ?></td>

                    <!-- Per-row update form -->
                    <td>
                      <form method="post" action="/cart/update" class="d-flex align-items-center" style="gap:8px;">
                        <div class="input-group">
                          <input type="number" name="quantity" value="<?= htmlspecialchars($it['quantity']) ?>" min="0" class="form-control form-control-sm qty-input" />
                          <input type="hidden" name="product_id" value="<?= htmlspecialchars($it['product_id']) ?>">
                          <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                        </div>
                        <div class="ms-2 text-muted small">available: <?= isset($it['stock']) ? (int)$it['stock'] : '—' ?></div>
                      </form>
                    </td>

                    <td class="text-end line-total">₹ <?= htmlspecialchars($it['line_total']) ?></td>

                    <td class="text-center">
                      <form method="post" action="/cart/delete" onsubmit="return confirm('Remove this item from cart?');">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($it['product_id']) ?>">
                        <button class="btn btn-danger btn-sm">Remove</button>
                      </form>
                    </td>
                  </tr>
                <?php $grand += (float)$it['line_total']; endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
              <a href="/" class="btn btn-outline-secondary">Continue shopping</a>
            </div>

            <div class="text-end">
              <div class="mb-2 text-muted">Grand total</div>
              <div class="h4 mb-0">₹ <?= number_format($grand, 2, '.', '') ?></div>
              <div class="mt-3">
                <a href="/checkout" class="btn btn-success">Proceed to Checkout</a>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div> <!-- card-body -->
    </div> <!-- card -->
  </div> <!-- container -->

  <!-- Bootstrap JS (optional) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
