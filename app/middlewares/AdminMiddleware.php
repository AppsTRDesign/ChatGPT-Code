<?php
class AdminMiddleware
{
    public function handle()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Forbidden'];
        }
        return true;
    }
}
