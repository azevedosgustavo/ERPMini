<?php

class Router
{
    private $routes = [];

    public function add($method, $pattern, $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch($method, $path)
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = rtrim($path, '/');
        $normalizedPath = $normalizedPath === '' ? '/' : $normalizedPath;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $normalizedMethod) {
                continue;
            }

            $pattern = preg_replace('#\{[a-zA-Z0-9_]+\}#', '([a-zA-Z0-9\-_]+)', $route['pattern']);
            $pattern = '#^' . rtrim($pattern, '/') . '$#';

            if (preg_match($pattern, $normalizedPath, $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found.'
        ]);
    }
}