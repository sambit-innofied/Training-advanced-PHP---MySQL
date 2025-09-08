<?php
require_once __DIR__ . '/../reports/ProductReport.php';

class AdminController
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function adminDashboard()
    {
        requireAdmin();

        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_orders,
                SUM(total_amount) AS total_revenue
            FROM orders
            WHERE status = 'paid'
        ");
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->query("
            SELECT
                p.id,
                p.name,
                SUM(oi.quantity) AS total_quantity_sold
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'paid'
            GROUP BY p.id, p.name
            ORDER BY total_quantity_sold DESC
            LIMIT 10
        ");
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function downloadReport()
    {
        requireAdmin();

        // Summary data
        $stmt = $this->pdo->query("
        SELECT
            COUNT(*) AS total_orders,
            SUM(total_amount) AS total_revenue
        FROM orders
        WHERE status = 'paid'
    ");
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Top-selling products data
        $stmt = $this->pdo->query("
        SELECT
            p.id,
            p.name,
            SUM(oi.quantity) AS total_quantity_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'paid'
        GROUP BY p.id, p.name
        ORDER BY total_quantity_sold DESC
        LIMIT 10
    ");
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new ProductReport();
        $pdf->generate($summary, $topProducts);
    }

}
