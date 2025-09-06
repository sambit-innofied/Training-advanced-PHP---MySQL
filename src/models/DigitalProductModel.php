<?php

class DigitalProductModel extends ProductModel
{
    private $file_size;
    private $download_url;

    public function getFileSize()
    {
        return $this->file_size;
    }

    public function setFileSize($file_size)
    {
        $this->file_size = $file_size;

        return $this;
    }

    public function getDownloadUrl()
    {
        return $this->download_url;
    }

    public function setDownloadUrl($download_url)
    {
        $this->download_url = $download_url;

        return $this;
    }
}
