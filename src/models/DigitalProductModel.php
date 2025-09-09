<?php

require_once __DIR__ . '/ProductModel.php';

class DigitalProductModel extends ProductModel
{
    private $file_size;
    private $download_url;

    public function __construct(?int $id = null, string $name = '', $price = null, string $type = 'digital', $category_id = null)
    {
        parent::__construct($id, $name, $price, $type, $category_id);
    }

    public function getFileSize() { return $this->file_size; }
    public function setFileSize($file_size) { $this->file_size = $file_size; return $this; }

    public function getDownloadUrl() { return $this->download_url; }
    public function setDownloadUrl($download_url) { $this->download_url = $download_url; return $this; }

    public function toArray(): array
    {
        $base = parent::toArray();
        $base['file_size'] = $this->file_size;
        $base['download_url'] = $this->download_url;
        return $base;
    }
}
