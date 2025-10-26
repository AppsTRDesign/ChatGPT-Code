<?php

namespace App\Controllers;

use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            redirect('admin/dashboard');
        }

        view('admin/login', ['title' => 'Yönetici Girişi']);
    }

    public function login(): void
    {
        if (!is_post()) {
            redirect('admin/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = (new User())->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            view('admin/login', [
                'title' => 'Yönetici Girişi',
                'error' => 'Geçersiz kullanıcı adı veya şifre.',
                'email' => $email,
            ]);
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];

        redirect('admin/dashboard');
    }

    public function logout(): void
    {
        session_destroy();
        redirect('admin/login');
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}
