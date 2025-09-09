<?php

require_once __DIR__ . '/ProductModel.php';

class PhysicalProductModel extends ProductModel
{
    private $weight;
    private $dimensions;

    public function __construct(?int $id = null, string $name = '', $price = null, string $type = 'physical', $category_id = null)
    {
        parent::__construct($id, $name, $price, $type, $category_id);
    }

    public function getWeight() { return $this->weight; }
    public function setWeight($weight) { $this->weight = $weight; return $this; }

    public function getDimensions() { return $this->dimensions; }
    public function setDimensions($dimensions) { $this->dimensions = $dimensions; return $this; }

    /**
     * Extend toArray to include subtype fields
     */
    public function toArray(): array
    {
        $base = parent::toArray();
        $base['weight'] = $this->weight;
        $base['dimensions'] = $this->dimensions;
        return $base;
    }
}