<?php
require 'db.php';
require 'product.php';

$productObj = new Product($pdo);

$id = $_GET['id'] ?? null;
if (!$id) {
  header('Location: index.php');
  exit;
}

$catsStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catsStmt->fetchAll();

$product = $productObj->getProduct($id);
if (!$product) {
  exit('Product not found.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price = $_POST['price'] ?? '0';
  $email = trim($_POST['email'] ?? '');
  $category_id = $_POST['category_id'] ?? null;
  $type = $_POST['type'] ?? '';

  $file_size = $_POST['file_size'] ?? null;
  $download_link = $_POST['download_link'] ?? null;
  $weight = $_POST['weight'] ?? null;
  $dimensions = $_POST['dimensions'] ?? null;

  if ($category_id === '')
    $category_id = null;

  if ($name === '')
    $errors[] = 'Name is required.';
  if (!is_numeric($price))
    $errors[] = 'Price must be numeric.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required.";
  }
  if ($type === '') {
    $errors[] = "Product type is required.";
  }

  if (empty($errors)) {
    $up = $pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, email = ?, category_id = ?, 
                type = ?, file_size = ?, download_link = ?, weight = ?, dimensions = ?
            WHERE id = ?
        ");
    $up->execute([
      $name,
      $description,
      $price,
      $email,
      $category_id,
      $type,
      $file_size,
      $download_link,
      $weight,
      $dimensions,
      $id
    ]);

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
        <ul><?php foreach ($errors as $e)
          echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Supplier Email</label>
        <input type="email" name="email" class="form-control" required
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description"
          class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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

      <div class="mb-3">
        <label class="form-label">Type *</label>
        <select name="type" id="type" class="form-select" required>
          <option value="">-- Select Type --</option>
          <option value="Digital" <?= (($_POST['type'] ?? '') === 'Digital') ? 'selected' : '' ?>>Digital</option>
          <option value="Physical" <?= (($_POST['type'] ?? '') === 'Physical') ? 'selected' : '' ?>>Physical</option>
        </select>
      </div>

      <div id="digital-fields" style="display:none;">
        <div class="mb-3">
          <label class="form-label">File Size (MB)</label>
          <input type="number" step="0.01" name="file_size" class="form-control"
            value="<?= htmlspecialchars($_POST['file_size'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Download Link</label>
          <input type="text" name="download_link" class="form-control"
            value="<?= htmlspecialchars($_POST['download_link'] ?? '') ?>">
        </div>
      </div>

      <div id="physical-fields" style="display:none;">
        <div class="mb-3">
          <label class="form-label">Weight (kg)</label>
          <input type="number" step="0.01" name="weight" class="form-control"
            value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Dimensions</label>
          <input type="text" name="dimensions" class="form-control" placeholder="e.g. 10x5x2 cm"
            value="<?= htmlspecialchars($_POST['dimensions'] ?? '') ?>">
        </div>
      </div>

      <button class="btn btn-primary" type="submit">Update</button>
      <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
    </form>
  </div>

  <script>
    function toggleTypeFields() {
      var val = document.getElementById('type').value;
      document.getElementById('digital-fields').style.display = (val === 'Digital') ? 'block' : 'none';
      document.getElementById('physical-fields').style.display = (val === 'Physical') ? 'block' : 'none';
    }
    document.getElementById('type').addEventListener('change', toggleTypeFields);
    toggleTypeFields();
  </script>
</body>

</html>