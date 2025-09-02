<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product List</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Product List</h2>
    <div>
      <?php if (isset($_SESSION['username'])): ?>
        <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 
          (<?= htmlspecialchars($_SESSION['role'] ?? 'customer') ?>)</span>
        
        <?php if (isAdmin()): ?>
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

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Price</th>
        <th>Type</th>
        <th>Category</th>
        <?php if (isAdmin()): ?>
          <th>Actions</th>
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
            <?php if (isAdmin()): ?>
              <td>
                <a href="/edit?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="/delete" style="display:inline-block;">
                  <input type="hidden" name="id" value="<?= $product['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="<?= isAdmin() ? '6' : '5' ?>" class="text-center">No products found</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>