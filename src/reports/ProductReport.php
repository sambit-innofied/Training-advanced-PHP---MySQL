<?php

class ProductReport extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Admin Dashboard Report', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    public function generate(array $summary, array $topProducts)
    {
        $this->AddPage();
        $this->SetFont('Arial', '', 12);

        // Summary Section
        $this->Cell(0, 10, 'Summary', 0, 1, 'L');
        $this->Ln(5);

        $this->Cell(60, 10, 'Total Orders:', 0, 0);
        $this->Cell(0, 10, $summary['total_orders'], 0, 1);

        $this->Cell(60, 10, 'Total Revenue:', 0, 0);
        $this->Cell(0, 10, '' . number_format((float)$summary['total_revenue'], 2, '.', ''), 0, 1);

        $this->Ln(10);

        // Top Selling Products Section
        $this->Cell(0, 10, 'Top-Selling Products', 0, 1, 'L');
        $this->Ln(5);

        $this->Cell(30, 10, 'Product ID', 1);
        $this->Cell(80, 10, 'Product Name', 1);
        $this->Cell(50, 10, 'Total Quantity Sold', 1);
        $this->Ln();

        foreach ($topProducts as $product) {
            $this->Cell(30, 10, $product['id'], 1);
            $this->Cell(80, 10, $product['name'], 1);
            $this->Cell(50, 10, $product['total_quantity_sold'], 1);
            $this->Ln();
        }

        $this->Output('D', 'admin_dashboard_report.pdf');
    }
}
