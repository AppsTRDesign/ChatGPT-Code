<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\ViewRenderer;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly AuthService $authService
    ) {
    }

    public function showLogin(Request $request, Response $response): Response
    {
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        return $this->view->render($response, 'auth/login', [
            'csrf_token' => $_SESSION['_csrf_token'] ?? '',
            'error' => $error,
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = is_array($data) ? trim((string) ($data['email'] ?? '')) : '';
        $password = is_array($data) ? (string) ($data['password'] ?? '') : '';

        $user = $this->authService->attemptLogin($email, $password);
        if ($user === null) {
            $_SESSION['login_error'] = 'Geçersiz kimlik bilgileri.';
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $_SESSION['login_error'] = null;
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
