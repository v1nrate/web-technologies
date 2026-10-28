<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes[] = [
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');

        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $path = rawurldecode($path);
        $path = $path === '/' ? '/' : rtrim($path, '/');

        foreach ($this->routes as $route) {
            $pattern = preg_replace_callback(
                '/\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}/',
                static fn(array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
                $route['path']
            );

            if (preg_match('#^' . $pattern . '$#u', $path, $matches)) {
                $parameters = [];

                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $parameters[] = $value;
                    }
                }

                [$controller, $method] = $route['handler'];
                $instance = new $controller();
                $instance->$method(...$parameters);
                return;
            }
        }

        http_response_code(404);
        echo 'Страница не найдена';
    }
}
