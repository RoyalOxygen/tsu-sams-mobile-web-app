<?php
declare(strict_types=1);

namespace App;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    private function map(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$this->normalize($path)] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir)) ?: '/';
        }
        $path = $this->normalize($uri);

        $handler = $this->routes[$method][$path] ?? null;
        $params = [];
        if ($handler === null) {
            foreach ($this->routes[$method] ?? [] as $pattern => $h) {
                $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
                if (preg_match($regex, $path, $m)) {
                    $handler = $h;
                    foreach ($m as $k => $v) {
                        if (!is_int($k)) {
                            $params[$k] = $v;
                        }
                    }
                    break;
                }
            }
        }
        if ($handler === null) {
            http_response_code(404);
            View::render('home/404', ['title' => 'Not found'], 'layouts/guest');
            return;
        }
        if ($method === 'POST') {
            Security::requireCsrf();
        }
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $ctrl = new $class();
            $ctrl->{$action}(...array_values($params));
            return;
        }
        $handler(...array_values($params));
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/index.php')) {
            $path = substr($path, 0, -10);
        }
        return $path === '' ? '/' : $path;
    }
}
