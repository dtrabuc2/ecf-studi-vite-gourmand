<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionMethod;
use RuntimeException;

final class Router
{
    private array $routes = [];

    public function setRoutes(array $routes): void
    {
        $this->routes = [];

        foreach ($routes as $route) {
            $this->addRoute(
                (string) ($route[0] ?? 'GET'),
                (string) ($route[1] ?? '/'),
                (string) ($route[2] ?? ''),
                array_values($route[3] ?? [])
            );
        }

        usort(
            $this->routes,
            static fn (array $left, array $right): int =>
                substr_count($left['uri'], '{') <=> substr_count($right['uri'], '{')
        );
    }

    public function addRoute(
        string $method,
        string $uri,
        string $action,
        array $middlewares = []
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $this->normalizePath($uri),
            'action' => $action,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $path = $this->normalizePath(
            (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')
        );
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $parameters = $this->match($route['uri'], $path);

            if ($parameters === null) {
                continue;
            }

            $this->runMiddlewareStack($route['middlewares']);
            $this->runAction($route['action'], $parameters);
            return;
        }

        Response::json(['success' => false, 'error' => 'Page introuvable.'], 404);
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

        if (!preg_match('#^' . $pattern . '$#u', $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];
        foreach ($names as $index => $name) {
            $parameters[$name] = rawurldecode((string) ($matches[$index] ?? ''));
        }

        return $parameters;
    }

    private function runMiddlewareStack(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $class = str_starts_with($middleware, 'App\\')
                ? $middleware
                : 'App\\Middleware\\' . $middleware;

            if (!class_exists($class)) {
                throw new RuntimeException('Middleware introuvable : ' . $class);
            }

            $instance = new $class();

            if (!is_callable($instance)) {
                throw new RuntimeException('Middleware non appelable : ' . $class);
            }

            $instance();
        }
    }

    private function runAction(string $action, array $parameters): void
    {
        if (!str_contains($action, '@')) {
            throw new RuntimeException('Action de route invalide : ' . $action);
        }

        [$controller, $method] = explode('@', $action, 2);

        $class = str_starts_with($controller, 'App\\')
            ? $controller
            : 'App\\Controller\\' . $controller;

        if (!class_exists($class)) {
            throw new RuntimeException('Contrôleur introuvable : ' . $class);
        }

        $instance = new $class();

        if (!method_exists($instance, $method)) {
            throw new RuntimeException('Action introuvable : ' . $class . '@' . $method);
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

            throw new RuntimeException(
                sprintf('Paramètre de route manquant : %s', $name)
            );
        }

        $reflection->invokeArgs($instance, $arguments);
    }

    private function castParameter(string $value, ?string $type): mixed
    {
        return match ($type) {
            'int' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            default => $value,
        };
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}
