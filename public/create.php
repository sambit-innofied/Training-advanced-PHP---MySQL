<?php
require 'db.php';
require 'validation.php';

$errors = [];
$success = '';
$name = '';
$description = '';
$price = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($error = validateRequired($name, "Product Name")) $errors[] = $error;
    if ($error = validateNumeric($price, "Price")) $errors[] = $error;
    if ($error = validateEmail($email)) $errors[] = $error;

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $price]);

        $success = "Product created successfully!";
        $name = $description = $price = $email = '';
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

        <button type="submit" class="btn btn-primary w-100">Create Product</button>
    </form>
</div>

<?php require 'footer.php'; ?>