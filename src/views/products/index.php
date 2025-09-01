<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product List</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <h2 class="mb-4">Product List</h2>

  <a href="/create" class="btn btn-success mb-3">Add Product</a>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>id</th>
        <th>Name</th>
        <th>Price</th>
        <th>Type</th>
        <th>Category</th>
        <th>Actions</th>
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
              <a href="/edit?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <form method="POST" action="/delete" style="display:inline-block;">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" class="text-center">No products found</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
