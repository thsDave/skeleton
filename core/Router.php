<?php

namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public function post(string $path, array $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = $this->pathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                $controller->$action(...$matches);
                return;
            }
        }

        http_response_code(404);
        $this->show404();
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function show404(): void
    {
        $page404 = dirname(__DIR__) . '/app/Views/errors/404.php';
        if (file_exists($page404)) {
            require $page404;
        } else {
            echo '<h1>404 - Página no encontrada</h1>';
        }
    }
}
