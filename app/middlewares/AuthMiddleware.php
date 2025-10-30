<?php
class AuthMiddleware
{
    public function handle()
    {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        return true;
    }
}
