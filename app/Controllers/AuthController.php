<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use RuntimeException;

final class AuthController
{
    public function __construct(private readonly AuthService $authService = new AuthService())
    {
    }

    public function register(Request $request): void
    {
        $payload = $request->json();
        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            Response::json(['error' => 'Invalid registration data'], 422);
            return;
        }

        try {
            $result = $this->authService->register($username, $email, $password);
            Response::json($result, 201);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function login(Request $request): void
    {
        $payload = $request->json();
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            Response::json(['error' => 'Invalid login data'], 422);
            return;
        }

        try {
            $result = $this->authService->login($email, $password);
            Response::json($result);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }
}
