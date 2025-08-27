<?php
require 'db.php';

$stmt = $pdo->query("
SELECT p.*, c.name AS category_name
from products p
left join categories c on p.category_id = c.id
order by p.created_at DESC
");

$products = $stmt->fetchAll();
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Products — Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h1 class="mb-4">Products</h1>
    <p>
      <a class="btn btn-primary" href="create.php">Add Product</a>
    </p>

    <?php if (count($products) === 0): ?>
      <div class="alert alert-info">No products yet.</div>
    <?php else: ?>
      <table class="table table-striped">
        <thead><tr>
          <th>id</th>
          <th>Name</th>
          <th>Description</th>
          <th>Supplier Email</th>
          <th>Price</th>
          <th>Created</th>
          <th>Categories</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['description']) ?></td>
            <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>
            <td><?= number_format($p['price'], 2) ?></td>
            <td><?= htmlspecialchars($p['created_at']) ?></td>
            <td><?= htmlspecialchars($p['category_name']) ?></td>
             <td>
              <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= urlencode($p['id']) ?>">Edit</a>

              <form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this product?');">
                <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>