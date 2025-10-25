<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Token;

class ApiController extends Controller
{
    public function notifications(): string
    {
        $this->ensureJson();
        $token = $this->authorize();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = json_decode((string)file_get_contents('php://input'), true);
            if (!$payload || !isset($payload['title'], $payload['message'])) {
                return $this->json(['error' => 'Eksik parametre'], 422);
            }

            $notification = Notification::create([
                'user_id' => (int)$token['user_id'],
                'title' => $payload['title'],
                'message' => $payload['message'],
                'link' => $payload['link'] ?? null,
                'channel' => 'api',
            ]);

            Token::incrementUsage((int)$token['id']);

            return $this->json(['status' => 'queued', 'notification' => $notification]);
        }

        $notifications = Notification::forUser((int)$token['user_id']);
        return $this->json(['notifications' => $notifications]);
    }

    public function stats(): string
    {
        $this->ensureJson();
        $token = $this->authorize();
        $stats = Notification::statsForUser((int)$token['user_id']);
        return $this->json(['stats' => $stats]);
    }

    public function tokens(): string
    {
        $this->ensureJson();
        $user = $this->session->user();
        if (!$user) {
            return $this->json(['error' => 'Kimlik doğrulama gerekli'], 401);
        }

        $token = Token::createForUser((int)$user['id']);
        return $this->json(['token' => $token]);
    }

    private function ensureJson(): void
    {
        header('Content-Type: application/json');
    }

    private function authorize(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            echo json_encode(['error' => 'Yetkisiz']);
            http_response_code(401);
            exit;
        }

        $key = substr($header, 7);
        $token = Token::findByKey($key);
        if (!$token || !(bool)$token['is_active']) {
            echo json_encode(['error' => 'Geçersiz token']);
            http_response_code(401);
            exit;
        }

        return $token;
    }

    private function json(array $payload, int $status = 200): string
    {
        http_response_code($status);
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
