<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionMethod;

final class Router
{
    private array $routes = [];

    public function addRoute(
        string $method,
        string $uri,
        string $controllerAction,
        array $middlewares = []
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'action' => $controllerAction,
            'middlewares' => $middlewares,
        ];
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = [];

        foreach ($routes as $route) {
            $this->addRoute(
                $route[0],
                $route[1],
                $route[2],
                $route[3] ?? []
            );
        }

        usort(
            $this->routes,
            static fn (array $left, array $right): int =>
                substr_count($left['uri'], '{') <=> substr_count($right['uri'], '{')
        );
    }

    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $parameters = $this->match($route['uri'], $path);

            if ($parameters === null) {
                continue;
            }

            foreach ($route['middlewares'] as $middleware) {
                $this->runMiddleware($middleware);
            }

            $this->runAction($route['action'], $parameters);
            return;
        }

        http_response_code(404);
        echo 'Page introuvable.';
    }

    private function match(string $routeUri, string $requestPath): ?array
    {
        $names = [];

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];
                return '([^/]+)';
            },
            $routeUri
        );

        if ($pattern === null) {
            return null;
        }

        if (!preg_match('#^' . $pattern . '$#', $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];
        foreach ($names as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }

    private function runMiddleware(string $middleware): void
    {
        $class = str_starts_with($middleware, 'App\\')
            ? $middleware
            : 'App\\Middleware\\' . $middleware;

        if (!class_exists($class)) {
            throw new \RuntimeException('Middleware introuvable : ' . $class);
        }

        $instance = new $class();

        if (!is_callable($instance)) {
            throw new \RuntimeException('Middleware non appelable : ' . $class);
        }

        $instance();
    }

    private function runAction(string $action, array $parameters): void
    {
        [$controller, $method] = explode('@', $action, 2);

        $class = str_starts_with($controller, 'App\\')
            ? $controller
            : 'App\\Controller\\' . $controller;

        if (!class_exists($class)) {
            throw new \RuntimeException('Contrôleur introuvable : ' . $class);
        }

        $instance = new $class();

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException('Action introuvable : ' . $class . '@' . $method);
        }

        $reflection = new ReflectionMethod($instance, $method);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $arguments[] = $this->castParameter(
                    $parameters[$name],
                    $parameter->getType()?->getName()
                );
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $arguments[] = null;
        }

        $reflection->invokeArgs($instance, $arguments);
    }

    private function castParameter(string $value, ?string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }
}