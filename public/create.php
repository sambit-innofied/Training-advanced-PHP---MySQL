<?php
require 'db.php';
require 'product.php';
require 'digital_product.php';
require 'physical_product.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $type        = $_POST['type'] ?? '';

    if ($name === '') {
        $errors[] = "Name is required.";
    }
    if ($price === '' || !is_numeric($price)) {
        $errors[] = "Valid price is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if ($type === '') {
        $errors[] = "Product type is required.";
    }

    if (empty($errors)) {
        if ($type === 'digital') {
            $product = new DigitalProduct($pdo);
            $product->setFileSize($_POST['file_size'] ?? null);
            $product->setDownloadLink($_POST['download_link'] ?? null);
        } elseif ($type === 'physical') {
            $product = new PhysicalProduct($pdo);
            $product->setWeight($_POST['weight'] ?? null);
            $product->setDimensions($_POST['dimensions'] ?? null);
        } else {
            $product = new Product($pdo);
        }

        $product->setName($name);
        $product->setDescription($description);
        $product->setPrice($price);
        $product->setMail($email);
        $product->setCategoryId($category_id);
        $product->setProductType($type);

        if ($product->addProduct()) {
            $success = "Product created successfully!";
        } else {
            $errors[] = "Failed to create product.";
        }
    }
}

$catsStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catsStmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Create Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

    <h2>Create Product</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Supplier Email *</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="col-md-4">
            <label class="form-label">Price *</label>
            <input type="number" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-control">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Type *</label>
            <select name="type" id="type" class="form-control" required>
                <option value="">-- Select Type --</option>
                <option value="digital" <?= (($_POST['type'] ?? '') === 'digital') ? 'selected' : '' ?>>Digital</option>
                <option value="physical" <?= (($_POST['type'] ?? '') === 'physical') ? 'selected' : '' ?>>Physical</option>
            </select>
        </div>

        <div id="digital-fields" class="col-12" style="display:none;">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">File Size (MB)</label>
                    <input type="number" step="0.01" name="file_size" class="form-control" value="<?= htmlspecialchars($_POST['file_size'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Download Link</label>
                    <input type="text" name="download_link" class="form-control" value="<?= htmlspecialchars($_POST['download_link'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div id="physical-fields" class="col-12" style="display:none;">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" step="0.01" name="weight" class="form-control" value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dimensions</label>
                    <input type="text" name="dimensions" class="form-control" placeholder="e.g. 10x5x2 cm" value="<?= htmlspecialchars($_POST['dimensions'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Create</button>
            <a href="index.php" class="btn btn-secondary">Back</a>
        </div>
    </form>

    <script>
        function toggleTypeFields() {
            var val = document.getElementById('type').value;
            document.getElementById('digital-fields').style.display = (val === 'digital') ? 'block' : 'none';
            document.getElementById('physical-fields').style.display = (val === 'physical') ? 'block' : 'none';
        }
        document.getElementById('type').addEventListener('change', toggleTypeFields);
        toggleTypeFields();
    </script>

</body>
</html>
