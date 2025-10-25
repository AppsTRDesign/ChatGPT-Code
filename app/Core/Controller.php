<?php

namespace App\Core;

use App\Models\Client;
use App\Models\User;

abstract class Controller
{
    protected Request $request;
    protected ?array $user = null;
    protected bool $userLoaded = false;
    protected ?array $client = null;
    protected bool $clientLoaded = false;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function view(string $template, array $data = []): string
    {
        $templatePath = __DIR__ . '/../Views/' . $template . '.php';
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View {$template} not found");
        }

        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    protected function requireAuth(array $roles = []): ?string
    {
        if (!isset($_SESSION['user_id'])) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Oturum açmalısınız'
                ], 401);
            }

            header('Location: /login');
            exit;
        }

        if ($roles && (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true))) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bu işlem için yetkiniz yok'
                ], 403);
            }

            http_response_code(403);
            return '403 Forbidden';
        }

        return null;
    }

    protected function jsonResponse(array $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json')
            || strtolower($requestedWith) === 'xmlhttprequest'
            || str_contains($contentType, 'application/json');
    }

    protected function payload(): array
    {
        $body = $this->request->json();
        return array_merge($this->request->all(), $body);
    }

    protected function currentUser(): ?array
    {
        if ($this->userLoaded) {
            return $this->user;
        }

        $this->userLoaded = true;
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $this->user = User::find((int) $userId);

        return $this->user;
    }

    protected function currentClient(): ?array
    {
        if ($this->clientLoaded) {
            return $this->client;
        }

        $this->clientLoaded = true;
        $user = $this->currentUser();
        if (!$user) {
            return null;
        }

        $this->client = Client::findByUser((int) $user['id']);

        return $this->client;
    }
}
