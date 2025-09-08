<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="container py-4">

    <h1>Admin Dashboard</h1>

    <div class="mb-4">
        <h3>Summary</h3>
        <ul class="list-group">
            <li class="list-group-item">Total Orders: <?= (int) ($summary['total_orders'] ?? 0) ?></li>
            <li class="list-group-item">Total Revenue: ₹
                <?= number_format((float) ($summary['total_revenue'] ?? 0), 2, '.', '') ?></li>
        </ul>
    </div>

    <div>
        <h3>Top-Selling Products</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Quantity Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($topProducts): ?>
                    <?php foreach ($topProducts as $product): ?>
                        <tr>
                            <td><?= (int) $product['id'] ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= (int) $product['total_quantity_sold'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="/admin/report" class="btn btn-primary">Download Product Report (PDF)</a>
    <a href="/" class="btn btn-secondary">Back to Home</a>
</body>

</html>