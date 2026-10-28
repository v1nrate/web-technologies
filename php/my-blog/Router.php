<?php
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes[] = [$path, $handler];
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $base = rtrim(BASE_PATH, '/');
        if ($base !== '' && $uri !== $base && !str_starts_with($uri, $base . '/')) {
            http_response_code(404);
            echo 'Страница не найдена';
            return;
        }
        $path = rawurldecode(substr($uri, strlen($base))) ?: '/';
        foreach ($this->routes as [$route, $handler]) {
            $pattern = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '([^/]+)', $route);
            if (preg_match('#^' . $pattern . '$#u', $path, $matches)) {
                array_shift($matches);
                [$class, $method] = $handler;
                (new $class())->$method(...$matches);
                return;
            }
        }
        http_response_code(404);
        echo 'Страница не найдена';
    }
}
