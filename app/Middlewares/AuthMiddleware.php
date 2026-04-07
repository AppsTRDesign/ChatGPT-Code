<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthMiddleware
{
    public function __construct(private readonly AuthService $authService = new AuthService())
    {
    }

    public function handle(Request $request): bool
    {
        $token = $request->bearerToken();
        $userId = $this->authService->resolveUserId($token);
        if (!$userId) {
            Response::json(['error' => 'Unauthorized'], 401);
            return false;
        }

        $_SERVER['AUTH_USER_ID'] = (string) $userId;
        return true;
    }
}
