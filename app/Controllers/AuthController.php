<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminUser;
use App\Models\PasswordReset;
use App\Support\Session;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Admin Girişi',
        ]);
    }

    public function login(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->json(['status' => 'error', 'message' => 'Kullanıcı adı ve şifre gerekli.'], 422);
        }

        $user = AdminUser::findByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->json(['status' => 'error', 'message' => 'Kimlik doğrulama başarısız.'], 401);
        }

        $_SESSION['auth'] = [
            'id' => $user['id'],
            'username' => $user['username'],
        ];

        $this->json(['status' => 'success', 'redirect' => '/admin']);
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        redirect('/admin/login');
    }

    public function showReset(): void
    {
        $this->view('auth/password-reset', [
            'title' => 'Şifre Sıfırlama',
        ]);
    }

    public function sendReset(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $this->json(['status' => 'error', 'message' => 'E-posta gerekli.'], 422);
        }

        $token = bin2hex(random_bytes(16));
        PasswordReset::deleteByEmail($email);
        PasswordReset::create([
            'email' => $email,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // In a real application, send email here using configured mail settings.

        $this->json(['status' => 'success', 'message' => 'Sıfırlama bağlantısı oluşturuldu.']);
    }
}
