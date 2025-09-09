<?php
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../validation/ProductValidator.php';

class ProductController
{
    protected $pdo;
    protected $productModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->productModel = new ProductRepository($pdo);
    }

    // Get all products (index)
    public function index()
    {
        // repository returns entity objects; map them to arrays for views (preserve previous behavior
        $entities = $this->productModel->all();
        $products = array_map(function ($entity) {
            return $entity->toArray();
        }, $entities);

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

        // Create appropriate entity and pass to repository
        if ($type === 'physical') {
            $productEntity = new PhysicalProductModel(null, $name, $price, 'physical', $category_id);
            $productEntity->setWeight($old['weight']);
            $productEntity->setDimensions($old['dimensions']);
        } else {
            $productEntity = new DigitalProductModel(null, $name, $price, 'digital', $category_id);
            $productEntity->setFileSize($old['file_size']);
            $productEntity->setDownloadUrl($old['download_url']);
        }

        try {
            $this->productModel->create($productEntity);

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

        // fetch product entity
        $productEntity = $this->productModel->find($id);

        if (!$productEntity) {
            header('Location: /');
            exit;
        }

        // build old values to populate the form (preserve previous array keys)
        $base = $productEntity->toArray();

        $old = [
            'id' => $id,
            'name' => $base['name'],
            'price' => $base['price'],
            'type' => $base['type'],
            'category_id' => $base['category_id']
        ];

        // subtype fields (toArray on subtype includes them if present)
        if ($base['type'] === 'physical') {
            $old['weight'] = $base['weight'] ?? '';
            $old['dimensions'] = $base['dimensions'] ?? '';
        } else {
            $old['file_size'] = $base['file_size'] ?? '';
            $old['download_url'] = $base['download_url'] ?? '';
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
            // Fetch the product entity to populate the form
            $productEntity = $this->productModel->find($id);

            if (!$productEntity) {
                header('Location: /');
                exit;
            }

            $base = $productEntity->toArray();

            // Build old values to populate the form (prefer POST values)
            $old = [
                'id' => $id,
                'name' => $_POST['name'] ?? $base['name'],
                'price' => $_POST['price'] ?? $base['price'],
                'type' => $_POST['type'] ?? $base['type'],
                'category_id' => $_POST['category_id'] ?? $base['category_id']
            ];

            // Add subtype fields (read from entity if not present in POST)
            if (($base['type'] ?? '') === 'physical') {
                $old['weight'] = $_POST['weight'] ?? ($base['weight'] ?? '');
                $old['dimensions'] = $_POST['dimensions'] ?? ($base['dimensions'] ?? '');
            } else {
                $old['file_size'] = $_POST['file_size'] ?? ($base['file_size'] ?? '');
                $old['download_url'] = $_POST['download_url'] ?? ($base['download_url'] ?? '');
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

        // Prepare entity for update (id must be present)
        if ($type === 'physical') {
            $entity = new PhysicalProductModel($id, $name, $price, 'physical', $category_id);
            $entity->setWeight(trim($_POST['weight']));
            $entity->setDimensions(trim($_POST['dimensions']));
        } else {
            $entity = new DigitalProductModel($id, $name, $price, 'digital', $category_id);
            $entity->setFileSize(trim($_POST['file_size']));
            $entity->setDownloadUrl(trim($_POST['download_url']));
        }

        try {
            $this->productModel->update($entity);

            header('Location: /');
            exit;
        } catch (Exception $e) {
            // Prepare data for error display (preserve previous behavior)
            $old = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'type' => $type,
                'category_id' => $category_id
            ];

            // Add subtype data
            if ($type === 'physical') {
                $old['weight'] = $entity->getWeight();
                $old['dimensions'] = $entity->getDimensions();
            } else {
                $old['file_size'] = $entity->getFileSize();
                $old['download_url'] = $entity->getDownloadUrl();
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
