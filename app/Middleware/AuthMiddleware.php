<?php
declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware
{
    public function handle(): bool
    {
        if (!isset($_SESSION['auth'])) {
            redirect('/admin/login');
            return false;
        }

        return true;
    }
}
