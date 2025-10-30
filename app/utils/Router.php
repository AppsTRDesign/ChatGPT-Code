<?php
class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $normalized = $this->normalizeRoutePath($path);
        $this->routes[strtoupper($method)][$normalized] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);
        $candidates = $this->candidatePaths($path);
        foreach ($candidates as $candidate) {
            if (isset($this->routes[$method][$candidate])) {
                $route = $this->routes[$method][$candidate];
                foreach ($route['middlewares'] as $middleware) {
                    $instance = new $middleware();
                    $response = $instance->handle();
                    if ($response !== true) {
                        echo json_encode($response);
                        return;
                    }
                }
                return call_user_func($route['handler']);
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        return;
    }

    private function normalizeRoutePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }

    private function candidatePaths(string $path): array
    {
        $paths = [];
        $raw = '/' . ltrim(preg_replace('#/+#', '/', $path), '/');
        $normalized = rtrim($raw, '/') ?: '/';
        $paths[] = $normalized;

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName) {
            $scriptName = '/' . ltrim(str_replace('\\', '/', $scriptName), '/');
            if (str_starts_with($normalized, $scriptName)) {
                $trimmed = substr($normalized, strlen($scriptName));
                $trimmed = $trimmed === '' ? '/' : $trimmed;
                $paths[] = rtrim('/' . ltrim($trimmed, '/'), '/') ?: '/';
            }
            $scriptDir = '/' . trim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($scriptDir && $scriptDir !== '/' && str_starts_with($normalized, $scriptDir)) {
                $trimmed = substr($normalized, strlen($scriptDir));
                $trimmed = $trimmed === '' ? '/' : $trimmed;
                $paths[] = rtrim('/' . ltrim($trimmed, '/'), '/') ?: '/';
            }
        }

        if (str_starts_with($normalized, '/index.php')) {
            $trimmed = substr($normalized, strlen('/index.php'));
            $trimmed = $trimmed === '' ? '/' : $trimmed;
            $paths[] = rtrim('/' . ltrim($trimmed, '/'), '/') ?: '/';
        }

        return array_values(array_unique($paths));
    }
}
