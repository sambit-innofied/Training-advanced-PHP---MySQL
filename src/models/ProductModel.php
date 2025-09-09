<?php

class ProductModel
{
    private $id;
    private $name;
    private $price;
    private $type;
    private $category_id;
    private $category_name;

    public function __construct(?int $id = null, string $name = '', $price = null, string $type = 'physical', $category_id = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->type = $type;
        $this->category_id = $category_id;
        $this->category_name = null;
    }

    // Basic getters / setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; return $this; }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    public function getPrice() { return $this->price; }
    public function setPrice($price) { $this->price = $price; return $this; }

    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; return $this; }

    public function getCategoryId() { return $this->category_id; }
    public function setCategoryId($category_id) { $this->category_id = $category_id; return $this; }  
    public function getCategoryName() { return $this->category_name; }
    public function setCategoryName($category_name) { $this->category_name = $category_name; return $this; }

    /**
     * Convert entity to array useful for repository create/update
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
        ];
    }
}