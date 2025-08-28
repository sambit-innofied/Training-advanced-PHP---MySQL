<?php
require 'db.php';
require 'product.php';

$productObj = new Product($pdo);
$products = $productObj->getAllProducts();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Product List</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="container py-4">
  <h2 class="mb-4">Product List</h2>

  <a href="create.php" class="btn btn-success mb-3">Add Product</a>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Email</th>
        <th>Category</th>
        <th>Type</th>
        <th>Details</th>
        <th>Created At</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($products) > 0): ?>
        <?php foreach ($products as $product): ?>
          <tr>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['description']) ?></td>
            <td><?= htmlspecialchars($product['price']) ?></td>
            <td><?= htmlspecialchars($product['email']) ?></td>
            <td><?= htmlspecialchars($product['category_name']) ?></td>

            <td><?= htmlspecialchars(ucfirst($product['type'])) ?></td>

            <td>
              <?php if ($product['type'] === 'Digital'): ?>
                Size: <?= htmlspecialchars($product['file_size']) ?> MB,
                Link: <?= htmlspecialchars($product['download_link']) ?>
              <?php elseif ($product['type'] === 'Physical'): ?>
                Weight: <?= htmlspecialchars($product['weight']) ?> kg,
                Dimensions: <?= htmlspecialchars($product['dimensions']) ?>
              <?php else: ?>
                -
              <?php endif; ?>

            </td>

            <td><?= htmlspecialchars($product['created_at']) ?></td>
            <td>
              <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <form method="POST" action="delete.php" style="display:inline-block;">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="9" class="text-center">No products found</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>

</html>