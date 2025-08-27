<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$catsStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catsStmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    exit('Product not found.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '0';
    $category_id = $_POST['category_id'] ?? null;
    if ($category_id === '') $category_id = null;

    if ($name === '') $errors[] = 'Name is required.';
    if (!is_numeric($price)) $errors[] = 'Price must be numeric.';

    if (empty($errors)) {
        $up = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ? WHERE id = ?");
        $up->execute([$name, $description, $price, $category_id, $id]);
        header('Location: index.php');
        exit;
    }
} else {
    $_POST = array_merge($_POST, $product);
}
?>


<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h1>Edit Product</h1>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Price</label>
        <input name="price" class="form-control" required value="<?= htmlspecialchars($_POST['price'] ?? '0.00') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select">
          <option value="">-- none --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="btn btn-primary" type="submit">Update</button>
      <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
    </form>
  </div>
</body>
</html>
