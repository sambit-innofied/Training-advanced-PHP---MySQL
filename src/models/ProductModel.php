<?php

class ProductModel
{
    private $id;
    private $name;
    private $price;
    private $type;
    private $category_id;

    protected $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    //getters and setters 
    public function getId(){ return $this->id; }
    public function setId($id){ return $this->id = $id; }

    public function getName()    { return $this->name; }
    public function setName($name)    { return $this->name = $name; }

    public function getPrice() { return $this->price; }
    public function setPrice($price){ return $this->price = $price; }

    public function getType(){ return $this->type; }
    public function setType($type){ return $this->type = $type; }

    public function getCategoryId(){ return $this->category_id; }
    public function setCategoryId($category_id){ return $this->category_id = $category_id; }
}
