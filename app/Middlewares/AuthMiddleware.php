<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            Response::json(['error' => 'Unauthorized'], 401);
            return false;
        }

        $_SERVER['AUTH_USER_ID'] = (string) $userId;
        return true;
    }
}
