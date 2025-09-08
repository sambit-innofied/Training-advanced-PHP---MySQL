<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Product List</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Product List</h2>

    <div>
      <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
        <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
          <small class="text-muted">(<?= htmlspecialchars($_SESSION['role'] ?? 'customer') ?>)</small>
          <small class="text-muted">(<?= htmlspecialchars($_SESSION['email'] ?? 'cant email') ?>)</small>
        </span>

        <?php if (function_exists('isAdmin') && isAdmin()): ?>
          <a href="/admin/dashboard" class="btn btn-primary btn-sm me-2">Dashboard</a>
          <a href="/create" class="btn btn-success btn-sm me-2">Add Product</a>
        <?php endif; ?>

        <form method="POST" action="/logout" style="display:inline-block;">
          <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
        </form>
      <?php else: ?>
        <a href="/login" class="btn btn-outline-primary btn-sm me-2">Login</a>
        <a href="/register" class="btn btn-outline-success btn-sm">Register</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body p-3">
      <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px">ID</th>
            <th>Name</th>
            <th style="width:120px">Price</th>
            <th style="width:120px">Type</th>
            <th>Category</th>
            <th style="width:180px">Buy</th>
            <?php if (function_exists('isAdmin') && isAdmin()): ?>
              <th style="width:180px">Admin Actions</th>
            <?php endif; ?>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= htmlspecialchars($product['id']) ?></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= htmlspecialchars($product['price']) ?></td>
                <td><?= htmlspecialchars($product['type']) ?></td>
                <td><?= htmlspecialchars($product['category_name'] ?? '') ?></td>

                <td>
                  <form method="post" action="/cart/add" class="d-inline">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn btn-sm btn-primary" type="submit">Add to cart</button>
                  </form>
                </td>

                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                  <td>
                    <a href="/edit?id=<?= (int) $product['id'] ?>" class="btn btn-warning btn-sm me-1">Edit</a>

                    <form method="POST" action="/delete" style="display:inline-block;">
                      <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                    </form>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="<?= (function_exists('isAdmin') && isAdmin()) ? '7' : '6' ?>" class="text-center">No products
                found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>

</html>