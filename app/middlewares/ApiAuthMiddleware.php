<?php
class ApiAuthMiddleware
{
    public function handle()
    {
        $headers = getallheaders();
        $key = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;
        if (is_string($key)) {
            $key = trim($key);
            if ($key === '') {
                $key = null;
            }
        }
        if (!$key) {
            if ($this->allowPublicAccess()) {
                return true;
            }
            http_response_code(401);
            return ['error' => 'API key missing'];
        }

        $apiKey = new ApiKey();
        $user = $apiKey->validate($key);
        if (!$user) {
            http_response_code(403);
            return ['error' => 'Invalid API key'];
        }
        $_SERVER['api_user'] = $user;
        return true;
    }

    private function allowPublicAccess(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $publicPaths = [
            '/api/menu',
            '/api/menu/order',
            '/api/menu/waiter-call',
            '/api/menu/order-status',
            '/api/menu/currency',
            '/api/menu/receipt',
        ];
        if (!in_array($path, $publicPaths, true)) {
            return false;
        }
        if ($path === '/api/menu') {
            return isset($_GET['slug']);
        }
        if ($path === '/api/menu/order-status') {
            return isset($_GET['slug'], $_GET['order_number']);
        }
        if ($path === '/api/menu/currency') {
            return isset($_GET['slug'], $_GET['to']);
        }
        if (in_array($path, ['/api/menu/order', '/api/menu/waiter-call', '/api/menu/receipt'], true)) {
            return true;
        }
        return false;
    }
}
