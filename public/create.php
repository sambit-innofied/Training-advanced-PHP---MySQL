<?php
require 'db.php';
require 'validation.php';
require 'product.php';

$productObj = new Product($pdo);

$errors = [];
$success = '';
$name = '';
$description = '';
$price = '';
$email = '';
$category_id = null;

$catsStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $email = $_POST['email'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    if ($category_id === '') $category_id = null;

    if ($error = validateRequired($name, "Product Name")) $errors[] = $error;
    if ($error = validateNumeric($price, "Price")) $errors[] = $error;
    if ($error = validateEmail($email)) $errors[] = $error;

    if (empty($errors)) {
        $productObj->addProduct($name, $description, $price, $email, $category_id);

        $success = "Product created successfully!";
        $name = $description = $price = $email = '';
        $category_id = null;
    }
}

?>

<?php require 'header.php'; ?>

<div class="card shadow-lg p-4 rounded-4">
    <h2 class="mb-4 text-center">Create Product</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
        </div>
    <?php endif ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Price *</label>
            <input type="text" name="price" class="form-control" value="<?= htmlspecialchars($price) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Supplier Email (optional)</label>
            <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($category_id == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Create Product</button>
    </form>
</div>

<?php require 'footer.php'; ?>
