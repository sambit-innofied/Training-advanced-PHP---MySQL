<?php
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../validation/ProductValidator.php';

class ProductController
{
    protected $pdo;
    protected $productModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->productModel = new ProductModel($pdo);
    }

    //Get all products (index)
    public function index()
    {
        $products = $this->productModel->all();

        // Check if user is admin
        isAdmin();

        include __DIR__ . '/../views/products/index.php';
    }

    // Render the create product form
    public function create(array $errors = [], array $old = [])
    {
        $categories = $this->productModel->categories();

        include __DIR__ . '/../views/products/create.php';
    }

    // Handle POST request and insert product + subtype row
    public function store()
    {
        $errors = ProductValidator::validate($this->pdo, $_POST);

        if (!empty($errors)) {
            return $this->create($errors, $_POST);
        }

        // Prepare data for insertion
        $name = trim($_POST['name']);
        $price = number_format((float) $_POST['price'], 2, '.', '');
        $type = $_POST['type'];
        $category_id = $_POST['category_id'];

        // Prepare old data for potential error handling
        $old = [
            'name' => $name,
            'price' => $price,
            'type' => $type,
            'category_id' => $category_id
        ];

        // Add subtype data to old array
        if ($type === 'physical') {
            $old['weight'] = trim($_POST['weight']);
            $old['dimensions'] = trim($_POST['dimensions']);
        } else {
            $old['file_size'] = trim($_POST['file_size']);
            $old['download_url'] = trim($_POST['download_url']);
        }

        // Prepare data array for model
        $data = array_merge($old, ['name' => $name, 'price' => $price, 'type' => $type, 'category_id' => $category_id]);

        try {
            $this->productModel->create($data);

            header('Location: /');
            exit;
        } catch (Exception $e) {
            $errors['database'] = 'Failed to save product: ' . $e->getMessage();
            return $this->create($errors, $old);
        }
    }

    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            header('Location: /');
            exit;
        }
        $id = (int) $id;

        // fetch product
        $product = $this->productModel->find($id);

        if (!$product) {
            header('Location: /');
            exit;
        }

        // build old values to populate the form
        $old = [
            'id' => $id,
            'name' => $product['name'],
            'price' => $product['price'],
            'type' => $product['type'],
            'category_id' => $product['category_id']
        ];

        // fetch subtype fields
        $sub = $this->productModel->getSubtype($id, $product['type']);
        if (!empty($sub)) {
            if ($product['type'] === 'physical') {
                $old['weight'] = $sub['weight'];
                $old['dimensions'] = $sub['dimensions'];
            } else {
                $old['file_size'] = $sub['file_size'];
                $old['download_url'] = $sub['download_url'];
            }
        }

        // load categories
        $categories = $this->productModel->categories();

        $errors = [];
        include __DIR__ . '/../views/products/edit.php';
    }

    public function update()
    {
        // require id
        $id_raw = $_POST['id'] ?? null;
        if (!$id_raw || !is_numeric($id_raw)) {
            header('Location: /');
            exit;
        }
        $id = (int) $id_raw;

        // Validate the input using static method
        $errors = ProductValidator::validate($this->pdo, $_POST);

        if (!empty($errors)) {
            // Fetch the product and its subtype data to populate the form
            $product = $this->productModel->find($id);

            if (!$product) {
                header('Location: /');
                exit;
            }

            // Build old values to populate the form
            $old = [
                'id' => $id,
                'name' => $_POST['name'] ?? $product['name'],
                'price' => $_POST['price'] ?? $product['price'],
                'type' => $_POST['type'] ?? $product['type'],
                'category_id' => $_POST['category_id'] ?? $product['category_id']
            ];

            // Add subtype fields (read from DB if not present in POST)
            $sub = $this->productModel->getSubtype($id, $product['type']);
            if (!empty($sub)) {
                if (($product['type'] ?? '') === 'physical') {
                    $old['weight'] = $_POST['weight'] ?? $sub['weight'];
                    $old['dimensions'] = $_POST['dimensions'] ?? $sub['dimensions'];
                } else {
                    $old['file_size'] = $_POST['file_size'] ?? $sub['file_size'];
                    $old['download_url'] = $_POST['download_url'] ?? $sub['download_url'];
                }
            }

            // Load categories for the form
            $categories = $this->productModel->categories();

            // Re-render the edit form with errors and old values
            include __DIR__ . '/../views/products/edit.php';
            return;
        }

        // If validation passes, process the update
        $name = trim($_POST['name']);
        $price = number_format((float) $_POST['price'], 2, '.', '');
        $type = $_POST['type'];
        $category_id = (int) $_POST['category_id'];

        // Prepare data for transaction
        $updateData = [
            'name' => $name,
            'price' => $price,
            'type' => $type,
            'category_id' => $category_id,
            'id' => $id
        ];

        // Add subtype data
        if ($type === 'physical') {
            $updateData['weight'] = trim($_POST['weight']);
            $updateData['dimensions'] = trim($_POST['dimensions']);
        } else {
            $updateData['file_size'] = trim($_POST['file_size']);
            $updateData['download_url'] = trim($_POST['download_url']);
        }

        try {
            $this->productModel->update($id, $updateData);

            header('Location: /');
            exit;
        } catch (Exception $e) {
            // Prepare data for error display
            $old = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'type' => $type,
                'category_id' => $category_id
            ];

            // Add subtype data
            if ($type === 'physical') {
                $old['weight'] = $updateData['weight'];
                $old['dimensions'] = $updateData['dimensions'];
            } else {
                $old['file_size'] = $updateData['file_size'];
                $old['download_url'] = $updateData['download_url'];
            }

            // Show error in form
            $errors['database'] = 'Failed to update product: ' . $e->getMessage();

            // Load categories for the form
            $categories = $this->productModel->categories();

            include __DIR__ . '/../views/products/edit.php';
            return;
        }
    }

    public function delete()
    {
        $id = $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            header('Location: /');
            exit;
        }
        $id = (int) $id;

        try {
            $this->productModel->delete($id);
        } catch (Exception $e) {
            // ignore - keep same behaviour as before
        }

        header('Location: /');
        exit;
    }
}
