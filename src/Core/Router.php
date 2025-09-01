<?php

class Router
{
    private $routes = [];

    public function get($path, $callback)  { $this->addRoute('GET', $path, $callback); }
    public function post($path, $callback) { $this->addRoute('POST', $path, $callback); }

    private function addRoute($method, $path, $callback)
    {
        $path = $this->normalizePath($path);
        $this->routes[$method][$path] = $callback;
    }

    private function normalizePath($path)
    {
        $uri = parse_url($path, PHP_URL_PATH) ?? $path;
        $uri = rtrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    public function dispatch($method, $requestUri)
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = $this->normalizePath($path);

        $callback = $this->routes[$method][$path] ?? null;

        if (!$callback) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        // if callback is callable (closure or [class, method]) call it
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            // allow strings like 'ProductController@index'
            if (is_string($callback) && strpos($callback, '@') !== false) {
                [$class, $method] = explode('@', $callback, 2);
                if (class_exists($class)) {
                    $instance = new $class($GLOBALS['pdo']); // pass pdo via global for simplicity
                    if (method_exists($instance, $method)) {
                        $instance->$method();
                        return;
                    }
                }
            }
            throw new Exception("Route callback is not callable");
        }
    }
}
