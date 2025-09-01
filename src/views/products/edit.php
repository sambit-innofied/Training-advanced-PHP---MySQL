<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="container py-4">
    <h2>Edit product</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/update" id="productForm" class="mb-4">
        <!-- IMPORTANT: hidden id so update() knows which product to update -->
        <input type="hidden" name="id" value="<?= htmlspecialchars($old['id'] ?? '') ?>">

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Price</label>
            <input name="price" class="form-control" value="<?= htmlspecialchars($old['price'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" id="typeSelect" class="form-select">
                <option value="">-- choose --</option>
                <option value="physical" <?= (isset($old['type']) && $old['type'] === 'physical') ? 'selected' : '' ?>>
                    Physical</option>
                <option value="digital" <?= (isset($old['type']) && $old['type'] === 'digital') ? 'selected' : '' ?>>
                    Digital</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">-- choose --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>" <?= (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- physical fields -->
        <div id="physicalFields" style="display:none;">
            <div class="mb-3">
                <label class="form-label">Weight (kg)</label>
                <input name="weight" class="form-control" value="<?= htmlspecialchars($old['weight'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Dimensions (L×W×H)</label>
                <input name="dimensions" class="form-control" value="<?= htmlspecialchars($old['dimensions'] ?? '') ?>">
            </div>
        </div>

        <!-- digital fields -->
        <div id="digitalFields" style="display:none;">
            <div class="mb-3">
                <label class="form-label">File size (MB)</label>
                <input name="file_size" class="form-control" value="<?= htmlspecialchars($old['file_size'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Download URL</label>
                <input name="download_url" class="form-control"
                    value="<?= htmlspecialchars($old['download_url'] ?? '') ?>">
            </div>
        </div>

        <button class="btn btn-primary">Update</button>
        <a href="/" class="btn btn-secondary">Cancel</a>
    </form>

    <script>
        function toggleFields() {
            const t = document.getElementById('typeSelect').value;
            document.getElementById('physicalFields').style.display = t === 'physical' ? 'block' : 'none';
            document.getElementById('digitalFields').style.display = t === 'digital' ? 'block' : 'none';
        }
        document.getElementById('typeSelect').addEventListener('change', toggleFields);
        toggleFields();
    </script>
</body>

</html>