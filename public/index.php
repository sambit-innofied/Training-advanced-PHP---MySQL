<?php

//to show errors in the browser.
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/db.php';
require __DIR__ . '/../src/Core/Router.php';

$router = new Router();

//Loading all the route definitions
require __DIR__.'/../src/routes/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);