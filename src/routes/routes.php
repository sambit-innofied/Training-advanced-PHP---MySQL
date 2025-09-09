<?php

require __DIR__ . '/../controllers/ProductController.php';
require __DIR__ . '/../controllers/AuthController.php';
require __DIR__ . '/../helpers/auth.php';
require __DIR__ . '/../controllers/CartController.php';
require __DIR__ . '/../controllers/AdminController.php';


$GLOBALS['pdo'] = $pdo;


// Auth routes (public)
$router->get('/login', 'AuthController@login');
$router->post('/authenticate', 'AuthController@authenticate');
$router->get('/register', 'AuthController@register');
$router->post('/store-user', 'AuthController@storeUser');
$router->post('/logout', 'AuthController@logout');

// Protected routes - all require authentication
$router->get('/', function () use ($pdo) {
  requireAuth();

  $controller = new ProductController($pdo);
  $controller->index();
});

$router->get('/create', function () use ($pdo) {
  requireAdmin();

  $controller = new ProductController($pdo);
  $controller->create();
});

$router->post('/store', function () use ($pdo) {
  requireAdmin();

  $controller = new ProductController($pdo);
  $controller->store();
});

$router->get('/edit', function () use ($pdo) {
  requireAdmin();

  $controller = new ProductController($pdo);
  $controller->edit();
});

$router->post('/update', function () use ($pdo) {
  requireAdmin();

  $controller = new ProductController($pdo);
  $controller->update();
});

$router->post('/delete', function () use ($pdo) {
  requireAdmin();

  $controller = new ProductController($pdo);
  $controller->delete();
});

//Cart routes (public)
$router->get('/cart', 'CartController@index');
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/delete', 'CartController@delete');
$router->get('/checkout', 'CartController@checkout');
$router->post('/checkout', 'CartController@placeOrder');

// create stripe checkout session (called via JS)
$router->post('/create-checkout-session', 'CartController@createCheckoutSession');

// stripe redirect landing pages
$router->get('/payment/success', 'CartController@paymentSuccess');
$router->get('/payment/cancel', 'CartController@paymentCancel');
$router->get('/admin/dashboard', 'AdminController@adminDashboard');
$router->get('/admin/report', 'AdminController@downloadReport');