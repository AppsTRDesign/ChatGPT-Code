<?php
class ApiAuthMiddleware
{
    public function handle()
    {
        $headers = getallheaders();
        $key = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;
        if (!$key) {
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
}
