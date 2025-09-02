<?php
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../validation/ProductValidator.php';

class ProductController
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // get all products and render view 
    public function index()
    {
        $sql = "
        SELECT p.id, p.name, p.price, p.type, p.category_id, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
        ";

        $stmt = $this->pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if user is admin
        isAdmin();

        include __DIR__ . '/../views/products/index.php';
    }

    // Render the create product form
    public function create(array $errors = [], array $old = [])
    {
        // load categories so the form can show them
        $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // view expects $categories, $errors, $old
        include __DIR__ . '/../views/products/create.php';
    }

    // Handle POST request and insert product + subtype row
    public function store()
    {
        // Validate the input using static method
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

        // Insert into DB inside a transaction
        try {
            $this->pdo->beginTransaction();

            $insertProduct = $this->pdo->prepare("
                INSERT INTO products (name, price, type, category_id)
                VALUES (:name, :price, :type, :category_id)
            ");
            $insertProduct->execute([
                ':name' => $name,
                ':price' => $price,
                ':type' => $type,
                ':category_id' => $category_id
            ]);

            $productId = $this->pdo->lastInsertId();

            if ($type === 'physical') {
                $insertPhysical = $this->pdo->prepare("
                    INSERT INTO physical_product (product_id, weight, dimensions)
                    VALUES (:product_id, :weight, :dimensions)
                ");
                $insertPhysical->execute([
                    ':product_id' => $productId,
                    ':weight' => $old['weight'],
                    ':dimensions' => $old['dimensions']
                ]);
            } else {
                $insertDigital = $this->pdo->prepare("
                    INSERT INTO digital_product (product_id, file_size, download_url)
                    VALUES (:product_id, :file_size, :download_url)
                ");
                $insertDigital->execute([
                    ':product_id' => $productId,
                    ':file_size' => $old['file_size'],
                    ':download_url' => $old['download_url']
                ]);
            }

            $this->pdo->commit();

            // success -> redirect to product list
            header('Location: /');
            exit;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            // show error in form
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
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);


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
        if ($product['type'] === 'physical') {
            $s = $this->pdo->prepare("SELECT weight, dimensions FROM physical_product WHERE product_id = ? LIMIT 1");
            $s->execute([$id]);
            $sub = $s->fetch(PDO::FETCH_ASSOC);
            if ($sub) {
                $old['weight'] = $sub['weight'];
                $old['dimensions'] = $sub['dimensions'];
            }
        } else {
            $s = $this->pdo->prepare("SELECT file_size, download_url FROM digital_product WHERE product_id = ? LIMIT 1");
            $s->execute([$id]);
            $sub = $s->fetch(PDO::FETCH_ASSOC);
            if ($sub) {
                $old['file_size'] = $sub['file_size'];
                $old['download_url'] = $sub['download_url'];
            }
        }


        // load categories
        $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

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

            // Add subtype fields
            if (($product['type'] ?? '') === 'physical') {
                $s = $this->pdo->prepare("SELECT weight, dimensions FROM physical_product WHERE product_id = ? LIMIT 1");
                $s->execute([$id]);
                $sub = $s->fetch(PDO::FETCH_ASSOC);
                if ($sub) {
                    $old['weight'] = $_POST['weight'] ?? $sub['weight'];
                    $old['dimensions'] = $_POST['dimensions'] ?? $sub['dimensions'];
                }
            } else {
                $s = $this->pdo->prepare("SELECT file_size, download_url FROM digital_product WHERE product_id = ? LIMIT 1");
                $s->execute([$id]);
                $sub = $s->fetch(PDO::FETCH_ASSOC);
                if ($sub) {
                    $old['file_size'] = $_POST['file_size'] ?? $sub['file_size'];
                    $old['download_url'] = $_POST['download_url'] ?? $sub['download_url'];
                }
            }

            // Load categories for the form
            $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        // Perform update inside transaction
        try {
            $this->pdo->beginTransaction();

            // Update products table
            $update = $this->pdo->prepare("UPDATE products SET name = ?, price = ?, type = ?, category_id = ? WHERE id = ?");
            $update->execute([$name, $price, $type, $category_id, $id]);

            // Remove any existing subtype rows
            $delP = $this->pdo->prepare("DELETE FROM physical_product WHERE product_id = ?");
            $delP->execute([$id]);

            $delD = $this->pdo->prepare("DELETE FROM digital_product WHERE product_id = ?");
            $delD->execute([$id]);

            // Insert new subtype row
            if ($type === 'physical') {
                $insertP = $this->pdo->prepare("INSERT INTO physical_product (product_id, weight, dimensions) VALUES (?, ?, ?)");
                $insertP->execute([$id, $updateData['weight'], $updateData['dimensions']]);
            } else {
                $insertD = $this->pdo->prepare("INSERT INTO digital_product (product_id, file_size, download_url) VALUES (?, ?, ?)");
                $insertD->execute([$id, $updateData['file_size'], $updateData['download_url']]);
            }

            $this->pdo->commit();

            // Redirect to product list on success
            header('Location: /');
            exit;
        } catch (Exception $e) {
            $this->pdo->rollBack();

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
            $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $this->pdo->beginTransaction();
            $delPhysical = $this->pdo->prepare("DELETE FROM physical_product WHERE product_id = ?");
            $delPhysical->execute([$id]);


            $delDigital = $this->pdo->prepare("DELETE FROM digital_product WHERE product_id = ?");
            $delDigital->execute([$id]);


            $delProduct = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $delProduct->execute([$id]);


            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
        }


        header('Location: /');
        exit;
    }
}
