<?php

namespace App\Utils;

use Exception;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $basePath = '';

    public function __construct()
    {
        $this->basePath = $this->getBasePath();
    }

    public function get(string $pattern, callable|array $handler): self
    {
        $this->addRoute('GET', $pattern, $handler);
        return $this;
    }

    public function post(string $pattern, callable|array $handler): self
    {
        $this->addRoute('POST', $pattern, $handler);
        return $this;
    }

    public function put(string $pattern, callable|array $handler): self
    {
        $this->addRoute('PUT', $pattern, $handler);
        return $this;
    }

    public function delete(string $pattern, callable|array $handler): self
    {
        $this->addRoute('DELETE', $pattern, $handler);
        return $this;
    }

    public function patch(string $pattern, callable|array $handler): self
    {
        $this->addRoute('PATCH', $pattern, $handler);
        return $this;
    }

    public function options(string $pattern, callable|array $handler): self
    {
        $this->addRoute('OPTIONS', $pattern, $handler);
        return $this;
    }

    public function any(string $pattern, callable|array $handler): self
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        foreach ($methods as $method) {
            $this->addRoute($method, $pattern, $handler);
        }
        return $this;
    }

    public function addMiddleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    private function addRoute(string $method, string $pattern, callable|array $handler): void
    {
        $pattern = $this->basePath . '/' . ltrim($pattern, '/');
        $pattern = preg_replace('/\//', '\\/', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*):([^\}]+)\}/', '(?P<$1>$2)', $pattern);
        $pattern = '/^' . $pattern . '\/?$/i';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $_GET[$key] = $value;
                    }
                }

                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                foreach ($this->middleware as $middleware) {
                    $result = $middleware();
                    if ($result === false) {
                        return;
                    }
                }

                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        $this->handleNotFound();
    }

    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;

            if (is_string($controllerClass)) {
                $controller = new $controllerClass();
            } else {
                $controller = $controllerClass;
            }

            if (!method_exists($controller, $method)) {
                throw new Exception("方法 {$method} 不存在于控制器中");
            }

            call_user_func_array([$controller, $method], $params);
            return;
        }

        throw new Exception('无效的路由处理器');
    }

    private function handleNotFound(): void
    {
        http_response_code(404);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => '404 - 页面未找到']);
            return;
        }

        include __DIR__ . '/../../views/errors/404.php';
    }

    private function getBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];

        $scriptDir = dirname($scriptName);
        if ($scriptDir === '/' || $scriptDir === '\\') {
            $scriptDir = '';
        }

        if (strpos($requestUri, $scriptName) === 0) {
            return $scriptDir;
        }

        if (strpos($requestUri, $scriptDir) === 0) {
            return $scriptDir;
        }

        return '';
    }

    public function generateUrl(string $path, array $params = []): string
    {
        $url = $this->basePath . '/' . ltrim($path, '/');

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $url .= '?' . $queryString;
        }

        return $url;
    }

    public function redirect(string $path, int $code = 302): void
    {
        $url = $this->generateUrl($path);
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
