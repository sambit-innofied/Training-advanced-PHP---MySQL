<?php

class PhysicalProductModel extends ProductModel
{
    private $weight;
    private $dimensions;

    public function getWeight(){ return $this->weight; }
    public function setWeight($weight) { return $this->weight = $weight; }

    public function getDimensions(){ return $this->dimensions; }
    public function setDimensions($dimensions){ return $this->dimensions = $dimensions; }
}
