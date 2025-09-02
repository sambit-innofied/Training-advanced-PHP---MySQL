<?php

class ProductValidator
{
    public static function validate(PDO $pdo, array $data): array
    {
        $errors = [];
        
        // Basic validation
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Name is required.';
        }
        
        if (empty($data['price']) || !is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors['price'] = 'Price must be a non-negative number.';
        }
        
        // Type validation
        if (empty($data['type']) || !in_array($data['type'], ['physical', 'digital'], true)) {
            $errors['type'] = 'Invalid product type.';
        }
        
        // Category validation
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Please select a category.';
        } else {
            $categoryErrors = self::validateCategory($pdo, $data['category_id'], $data['type'] ?? '');
            $errors = array_merge($errors, $categoryErrors);
        }
        
        // Subtype validation
        if (($data['type'] ?? '') === 'physical') {
            $physicalErrors = self::validatePhysicalProduct($data);
            $errors = array_merge($errors, $physicalErrors);
        } else if (($data['type'] ?? '') === 'digital') {
            $digitalErrors = self::validateDigitalProduct($data);
            $errors = array_merge($errors, $digitalErrors);
        }
        
        return $errors;
    }
    
    private static function validateCategory(PDO $pdo, $categoryId, $productType): array
    {
        $errors = [];
        
        $stmt = $pdo->prepare("SELECT id, type FROM categories WHERE id = ? LIMIT 1");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            $errors['category_id'] = 'Selected category does not exist.';
        } elseif ($category['type'] !== $productType) {
            $errors['category_id'] = 'Selected category type does not match product type.';
        }
        
        return $errors;
    }
    
    private static function validatePhysicalProduct(array $data): array
    {
        $errors = [];
        
        if (empty($data['weight']) || !is_numeric($data['weight']) || (float) $data['weight'] <= 0) {
            $errors['weight'] = 'Weight must be a positive number.';
        }
        
        if (empty(trim($data['dimensions'] ?? ''))) {
            $errors['dimensions'] = 'Dimensions are required.';
        }
        
        return $errors;
    }
    
    private static function validateDigitalProduct(array $data): array
    {
        $errors = [];
        
        if (empty($data['file_size']) || !is_numeric($data['file_size']) || (float) $data['file_size'] <= 0) {
            $errors['file_size'] = 'File size must be a positive number (MB).';
        }
        
        if (empty(trim($data['download_url'] ?? ''))) {
            $errors['download_url'] = 'Please enter a valid download URL.';
        }
        
        return $errors;
    }
}