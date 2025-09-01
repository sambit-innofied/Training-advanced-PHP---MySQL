<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/db.php';
require __DIR__ . '/../src/Core/Router.php';
require __DIR__ . '/../src/controllers/ProductController.php';

$GLOBALS['pdo'] = $pdo;

$router = new Router();

// route: home -> product list
$router->get('/', function() use ($pdo) {
    $controller = new ProductController($pdo);
    $controller->index(); // controller renders the view
});

$router->get('/create', 'ProductController@create');
$router->post('/store', 'ProductController@store');

$router->get('/edit', 'ProductController@edit');
$router->post('/update', 'ProductController@update');

$router->post('/delete', 'ProductController@delete');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
