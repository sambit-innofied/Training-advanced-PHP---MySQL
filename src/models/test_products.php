<?php
require 'db.php';
require 'PhysicalProduct.php';
require 'DigitalProduct.php';

$phone = new PhysicalProduct($pdo);
$phone->setName("iPhone 15");
$phone->setDescription("Latest Apple smartphone");
$phone->setPrice(999.99);
$phone->setMail("apple@supplier.com");
$phone->setCategoryId(1);
$phone->setWeight("200g");
$phone->setDimension("146.7 x 71.5 x 7.8 mm");
$phone->addProduct();

echo "Physical product added: " . $phone->getName() . " (" . $phone->getWeight() . ")\n";


$ebook = new DigitalProduct($pdo);
$ebook->setName("Learn PHP eBook");
$ebook->setDescription("Comprehensive PHP guide");
$ebook->setPrice(19.99);
$ebook->setMail("publisher@example.com");
$ebook->setCategoryId(2);
$ebook->setFileSize("5MB");
$ebook->setDownloadLink("https://example.com/ebook-download");
$ebook->addProduct();

echo "Digital product added: " . $ebook->getName() . " (" . $ebook->getFileSize() . ")\n";
