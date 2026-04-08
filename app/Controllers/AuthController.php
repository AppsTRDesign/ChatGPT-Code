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
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $result['user_id'];
            Response::json(['success' => true, 'user_id' => (int) $result['user_id']], 201);
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
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $result['user_id'];
            Response::json(['success' => true, 'user_id' => (int) $result['user_id']]);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        Response::json(['success' => true]);
    }
}
