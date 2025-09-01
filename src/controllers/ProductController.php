<?php
require_once __DIR__ . '/../models/ProductModel.php';

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
        // Basic validation & sanitization
        $name = trim($_POST['name'] ?? '');
        $price = $_POST['price'] ?? '';
        $type = $_POST['type'] ?? '';
        $category_id = $_POST['category_id'] ?? null;

        $errors = [];
        $old = ['name' => $name, 'price' => $price, 'type' => $type, 'category_id' => $category_id];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($price === '' || !is_numeric($price) || (float) $price < 0) {
            $errors['price'] = 'Price must be a non-negative number.';
        } else {
            $price = number_format((float) $price, 2, '.', '');
        }

        if (!in_array($type, ['physical', 'digital'], true)) {
            $errors['type'] = 'Invalid product type.';
        }

        // category must exist and match the product type
        $catStmt = $this->pdo->prepare("SELECT id, type FROM categories WHERE id = ? LIMIT 1");
        $catStmt->execute([$category_id]);
        $category = $catStmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $errors['category_id'] = 'Selected category does not exist.';
        } elseif ($category['type'] !== $type) {
            $errors['category_id'] = 'Selected category type does not match product type.';
        }

        // subtype fields
        if ($type === 'physical') {
            $weight = trim($_POST['weight'] ?? '');
            $dimensions = trim($_POST['dimensions'] ?? '');

            if ($weight === '' || !is_numeric($weight) || (float) $weight <= 0) {
                $errors['weight'] = 'Weight must be a positive number.';
            }

            if ($dimensions === '') {
                $errors['dimensions'] = 'Dimensions are required.';
            }

            $old['weight'] = $weight;
            $old['dimensions'] = $dimensions;
        } else { // digital
            $file_size = trim($_POST['file_size'] ?? '');
            $download_url = trim($_POST['download_url'] ?? '');

            if ($file_size === '' || !is_numeric($file_size) || (float) $file_size <= 0) {
                $errors['file_size'] = 'File size must be a positive number (MB).';
            }

            if ($download_url === '') {
                $errors['download_url'] = 'Please enter a valid download URL.';
            }

            $old['file_size'] = $file_size;
            $old['download_url'] = $download_url;
        }

        // If validation fails, re-render create form with errors and old values
        if (!empty($errors)) {
            return $this->create($errors, $old);
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


        // read inputs
        $name = trim($_POST['name'] ?? '');
        $price = $_POST['price'] ?? '';
        $type = $_POST['type'] ?? '';
        $category_id_raw = $_POST['category_id'] ?? null;


        $errors = [];
        $old = ['id' => $id, 'name' => $name, 'price' => $price, 'type' => $type, 'category_id' => $category_id_raw];


        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }


        if ($price === '' || !is_numeric($price) || (float) $price < 0) {
            $errors['price'] = 'Price must be a non-negative number.';
        } else {
            $price = number_format((float) $price, 2, '.', '');
            $old['price'] = $price;
        }


        if (!in_array($type, ['physical', 'digital'], true)) {
            $errors['type'] = 'Invalid product type.';
        }


        // category validation
        $category_id = null;
        if ($category_id_raw !== null && $category_id_raw !== '') {
            if (!is_numeric($category_id_raw) || (int) $category_id_raw <= 0) {
                $errors['category_id'] = 'Invalid category selected.';
            } else {
                $category_id = (int) $category_id_raw;
                $old['category_id'] = $category_id;
            }
        } else {
            $errors['category_id'] = 'Please choose a category.';
        }


        if (empty($errors)) {
            $catStmt = $this->pdo->prepare("SELECT id, type FROM categories WHERE id = ? LIMIT 1");
            $catStmt->execute([$category_id]);
            $category = $catStmt->fetch(PDO::FETCH_ASSOC);


            if (!$category) {
                $errors['category_id'] = 'Selected category does not exist.';
            } elseif ($category['type'] !== $type) {
                $errors['category_id'] = 'Selected category type does not match product type.';
            }
        }


        // subtype fields validation
        if ($type === 'physical') {
            $weight = trim($_POST['weight'] ?? '');
            $dimensions = trim($_POST['dimensions'] ?? '');


            if ($weight === '' || !is_numeric($weight) || (float) $weight <= 0) {
                $errors['weight'] = 'Weight must be a positive number.';
            }
            if ($dimensions === '') {
                $errors['dimensions'] = 'Dimensions are required.';
            }
            $old['weight'] = $weight;
            $old['dimensions'] = $dimensions;
        } else { // digital
            $file_size = trim($_POST['file_size'] ?? '');
            $download_url = trim($_POST['download_url'] ?? '');


            if ($file_size === '' || !is_numeric($file_size) || (float) $file_size <= 0) {
                $errors['file_size'] = 'File size must be a positive number (MB).';
            }
            if ($download_url === '') {
                $errors['download_url'] = 'Please enter a valid download URL.';
            }
            $old['file_size'] = $file_size;
            $old['download_url'] = $download_url;
        }


        // if validation fails -> re-render edit form
        if (!empty($errors)) {
            // load categories for the form
            $stmt = $this->pdo->query("SELECT id, name, type FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            include __DIR__ . '/../views/products/edit.php';
            return;
        }


        // perform update inside transaction
        try {
            $this->pdo->beginTransaction();


            // update products table
            $update = $this->pdo->prepare("UPDATE products SET name = ?, price = ?, type = ?, category_id = ? WHERE id = ?");
            $update->execute([$name, $price, $type, $category_id, $id]);


            // remove any existing subtype rows (simple approach)
            $delP = $this->pdo->prepare("DELETE FROM physical_product WHERE product_id = ?");
            $delP->execute([$id]);


            $delD = $this->pdo->prepare("DELETE FROM digital_product WHERE product_id = ?");
            $delD->execute([$id]);


            // insert new subtype row
            if ($type === 'physical') {
                $insertP = $this->pdo->prepare("INSERT INTO physical_product (product_id, weight, dimensions) VALUES (?, ?, ?)");
                $insertP->execute([$id, $old['weight'], $old['dimensions']]);
            } else {
                $insertD = $this->pdo->prepare("INSERT INTO digital_product (product_id, file_size, download_url) VALUES (?, ?, ?)");
                $insertD->execute([$id, $old['file_size'], $old['download_url']]);
            }


            $this->pdo->commit();


            header('Location: /');
            exit;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $errors['database'] = 'Failed to update product: ' . $e->getMessage();
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
