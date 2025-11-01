<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\AuthService;
use Core\Response;

$auth = new AuthService();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        Response::json(['error' => 'Desteklenmeyen istek yöntemi'], 405);
    }

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $payload['action'] ?? 'login';

    if ($action === 'login') {
        $user = $auth->attempt($payload['email'] ?? '', $payload['password'] ?? '');
        Response::json([
            'user' => $user,
            'message' => 'Giriş başarılı. Hoş geldiniz ' . $user['name'] . '!',
        ]);
    }

    if ($action === 'logout') {
        $auth->logout();
        Response::json([
            'message' => 'Oturum kapatıldı.',
        ]);
    }

    if ($action === 'reset') {
        $auth->resetPassword($payload['email'] ?? '');
        Response::json([
            'message' => 'Yeni şifreniz e-posta adresinize gönderildi.',
        ]);
    }

    if ($action === 'update-password') {
        $auth->updatePassword((int)$payload['user_id'], $payload['current_password'] ?? '', $payload['new_password'] ?? '');
        Response::json([
            'message' => 'Şifreniz güncellendi.',
        ]);
    }

    if ($action === 'update-profile') {
        $user = $auth->updateProfile((int)$payload['user_id'], $payload['name'] ?? '', $payload['email'] ?? '');
        Response::json([
            'user' => $user,
            'message' => 'Kullanıcı bilgileriniz güncellendi.',
        ]);
    }

    Response::json(['error' => 'Geçersiz işlem'], 400);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}
