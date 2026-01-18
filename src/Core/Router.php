<?php
/**
 * Router Class
 * 
 * Handles URL routing with support for RESTful routes,
 * attribute routing, and SEO-friendly URLs.
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private string $basePath = '';
    private array $middleware = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';
    private ?Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Add a GET route
     */
    public function get(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    /**
     * Add a POST route
     */
    public function post(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    /**
     * Add a PUT route
     */
    public function put(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    /**
     * Add a PATCH route
     */
    public function patch(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }

    /**
     * Add a DELETE route
     */
    public function delete(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    /**
     * Add route for multiple methods
     */
    public function match(array $methods, string $path, callable|array $handler, ?string $name = null): self
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $name);
        }
        return $this;
    }

    /**
     * Add route for all methods
     */
    public function any(string $path, callable|array $handler, ?string $name = null): self
    {
        return $this->match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $handler, $name);
    }

    /**
     * Group routes with common prefix and middleware
     */
    public function group(array $options, callable $callback): self
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . ($options['prefix'] ?? '');
        $this->groupMiddleware = array_merge($previousMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;

        return $this;
    }

    /**
     * Add middleware to the next route
     */
    public function middleware(string|array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    /**
     * Add a route
     */
    private function addRoute(string $method, string $path, callable|array $handler, ?string $name = null): self
    {
        $fullPath = $this->groupPrefix . $path;
        $pattern = $this->convertToRegex($fullPath);
        
        $route = [
            'method' => $method,
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $this->middleware),
            'name' => $name,
        ];

        $this->routes[] = $route;
        
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        $this->middleware = [];

        return $this;
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex(string $path): string
    {
        // Convert route parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = preg_replace('/\{([a-zA-Z_]+):([^}]+)\}/', '(?P<$1>$2)', $pattern);
        
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch the request to the appropriate handler
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Handle method override for PUT/PATCH/DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    $middlewareClass = "App\\Middleware\\{$middleware}";
                    if (class_exists($middlewareClass)) {
                        if ($this->container) {
                            $middlewareInstance = $this->container->get($middlewareClass);
                        } else {
                            $middlewareInstance = new $middlewareClass();
                        }
                        
                        $result = $middlewareInstance->handle();
                        if ($result !== true) {
                            return $result;
                        }
                    }
                }

                // Call handler
                return $this->callHandler($route['handler'], $params);
            }
        }

        // No route found
        http_response_code(404);
        return ['error' => 'Route not found'];
    }

    /**
     * Call the route handler
     */
    private function callHandler(callable|array $handler, array $params): mixed
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            
            if (is_string($controller)) {
                if ($this->container) {
                    $controller = $this->container->get($controller);
                } else {
                    $controller = new $controller();
                }
            }

            return call_user_func_array([$controller, $method], $params);
        }

        throw new \RuntimeException('Invalid route handler');
    }

    /**
     * Generate URL for a named route
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route '{$name}' not found");
        }

        $path = $this->namedRoutes[$name]['path'];

        foreach ($params as $key => $value) {
            $path = preg_replace('/\{' . $key . '(:.*?)?\}/', $value, $path);
        }

        return $this->basePath . $path;
    }

    /**
     * Set base path
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = rtrim($basePath, '/');
        return $this;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Register RESTful resource routes
     */
    public function resource(string $name, string $controller): self
    {
        $this->get("/{$name}", [$controller, 'index'], "{$name}.index");
        $this->get("/{$name}/create", [$controller, 'create'], "{$name}.create");
        $this->post("/{$name}", [$controller, 'store'], "{$name}.store");
        $this->get("/{$name}/{id}", [$controller, 'show'], "{$name}.show");
        $this->get("/{$name}/{id}/edit", [$controller, 'edit'], "{$name}.edit");
        $this->put("/{$name}/{id}", [$controller, 'update'], "{$name}.update");
        $this->delete("/{$name}/{id}", [$controller, 'destroy'], "{$name}.destroy");

        return $this;
    }

    /**
     * Register API resource routes (without create/edit views)
     */
    public function apiResource(string $name, string $controller): self
    {
        $this->get("/{$name}", [$controller, 'index'], "api.{$name}.index");
        $this->post("/{$name}", [$controller, 'store'], "api.{$name}.store");
        $this->get("/{$name}/{id}", [$controller, 'show'], "api.{$name}.show");
        $this->put("/{$name}/{id}", [$controller, 'update'], "api.{$name}.update");
        $this->delete("/{$name}/{id}", [$controller, 'destroy'], "api.{$name}.destroy");

        return $this;
    }
}
