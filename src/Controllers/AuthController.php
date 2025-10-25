<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\Mailer;
use App\Support\View;

class AuthController extends Controller
{
    public function showLanding(): string
    {
        if ($this->session->isAuthenticated()) {
            $user = $this->session->user();
            return $user['role'] === 'admin'
                ? $this->redirect('/admin/dashboard')
                : $this->redirect('/app/dashboard');
        }

        return $this->view('auth/landing', [
            'title' => 'NoaSoft WebPush Platformu',
            'layout' => 'public',
        ]);
    }

    public function login(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = User::findByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                if (!(bool)$user['is_active']) {
                    $this->session->flash('warning', 'Hesabınız aktif değil. Lütfen mail onayınızı tamamlayın.');
                } else {
                    $this->session->login($user);
                    return $this->redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/app/dashboard');
                }
            } else {
                $this->session->flash('danger', 'Geçersiz giriş bilgileri.');
            }
        }

        return $this->view('auth/login', [
            'title' => 'Giriş Yap',
            'layout' => 'public',
        ]);
    }

    public function register(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT),
            ];

            if (!$data['name'] || !$data['email'] || empty($_POST['password'])) {
                $this->session->flash('danger', 'Tüm alanları doldurun.');
            } elseif (User::findByEmail($data['email'])) {
                $this->session->flash('warning', 'Bu email ile kayıtlı bir hesap bulunuyor.');
            } else {
                User::create($data);
                $mailer = new Mailer();
                $sent = $mailer->send($data['email'], 'NoaSoft WebPush Aktivasyon', 'Hesabınızı aktifleştirmek için giriş yapın.');
                if ($sent) {
                    $this->session->flash('success', 'Kayıt başarıyla oluşturuldu. Lütfen mail onayı tamamlayın.');
                } else {
                    $this->session->flash('warning', 'Kayıt oluşturuldu ancak aktivasyon maili gönderilemedi. Lütfen yöneticiyle iletişime geçin.');
                }
                return $this->redirect('/login');
            }
        }

        return $this->view('auth/register', [
            'title' => 'Üye Ol',
            'layout' => 'public',
        ]);
    }

    public function forgotPassword(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->session->flash('info', 'Şifre sıfırlama yönergeleri mail adresinize gönderildi.');
        }

        return $this->view('auth/forgot-password', [
            'title' => 'Şifremi Unuttum',
            'layout' => 'public',
        ]);
    }

    public function resendActivation(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->session->flash('success', 'Aktivasyon maili tekrar gönderildi.');
        }

        return $this->view('auth/resend-activation', [
            'title' => 'Aktivasyon Mailini Yeniden Gönder',
            'layout' => 'public',
        ]);
    }

    public function logout(): string
    {
        $this->session->logout();
        return $this->redirect('/login');
    }

    private function redirect(string $path): string
    {
        header('Location: ' . $path);
        return '';
    }
}
