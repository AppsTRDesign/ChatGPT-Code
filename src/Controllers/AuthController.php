<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Core\DB;
use App\Services\AuthSecurityService;
use App\Services\GameService;

final class AuthController
{
    public function __construct(private readonly array $config)
    {
    }

    public function showLogin(): void
    {
        View::render('auth/login', ['csrf' => Csrf::token(), 'config' => $this->config]);
    }

    public function showRegister(): void
    {
        View::render('auth/register', ['csrf' => Csrf::token(), 'config' => $this->config]);
    }

    public function showForgot(): void
    {
        View::render('auth/forgot', ['csrf' => Csrf::token(), 'config' => $this->config]);
    }

    public function showReset(): void
    {
        View::render('auth/reset', ['csrf' => Csrf::token(), 'config' => $this->config, 'token' => (string) ($_GET['token'] ?? '')]);
    }

    public function register(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/register?error=csrf');
        }

        $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $email = (string) ($_POST['email'] ?? '');

        $guard = new AuthSecurityService();
        if (!$guard->allow('register', mb_strtolower(trim($email)), $ip, 10, 900, 1200)) {
            Response::redirect('/register?error=' . urlencode('Çok fazla deneme. Lütfen sonra tekrar dene.'));
        }

        $result = (new GameService())->register(
            (string) ($_POST['username'] ?? ''),
            $email,
            (string) ($_POST['password'] ?? ''),
            $ip
        );

        if (!$result['ok']) {
            $guard->registerFailure('register', mb_strtolower(trim($email)), $ip, 10, 1200);
            Response::redirect('/register?error=' . urlencode($result['message']));
        }

        $guard->clearFailures('register', mb_strtolower(trim($email)), $ip);
        Auth::loginUser((int) $result['user_id']);
        Response::redirect('/?toast=' . urlencode('Hoş geldin, kayıt tamamlandı.'));
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/login?error=csrf');
        }

        $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $guard = new AuthSecurityService();

        if (!$guard->allow('login', $email, $ip, 8, 900, 1800)) {
            Response::redirect('/login?error=' . urlencode('Çok fazla giriş denemesi. 30 dakika sonra tekrar dene.'));
        }

        $result = (new GameService())->login($email, (string) ($_POST['password'] ?? ''));
        if (!$result['ok']) {
            $guard->registerFailure('login', $email, $ip, 8, 1800);
            Response::redirect('/login?error=' . urlencode('E-posta veya şifre hatalı.'));
        }

        $guard->clearFailures('login', $email, $ip);
        Auth::loginUser((int) $result['user_id']);

        $stmt = DB::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => (int) $result['user_id']]);

        Response::redirect('/?toast=' . urlencode('Tekrar hoş geldin!'));
    }

    public function forgot(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/forgot-password?error=csrf');
        }

        $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $guard = new AuthSecurityService();

        if (!$guard->allow('forgot_password', $email, $ip, 5, 900, 1800)) {
            Response::redirect('/forgot-password?error=' . urlencode('Çok fazla deneme.'));
        }

        $userId = $guard->findUserIdByEmail($email);
        if ($userId !== null) {
            $token = $guard->issuePasswordReset($userId, $ip);
            // şimdilik mail yerine debug token (dev hızlandırma)
            $_SESSION['debug_reset_token'] = $token;
        }

        Response::redirect('/forgot-password?toast=' . urlencode('Eğer hesap varsa sıfırlama bağlantısı üretildi.'));
    }

    public function reset(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/reset-password?error=csrf');
        }

        $token = (string) ($_POST['token'] ?? '');
        $newPassword = (string) ($_POST['password'] ?? '');

        $ok = (new AuthSecurityService())->consumePasswordReset($token, $newPassword);
        if (!$ok) {
            Response::redirect('/reset-password?token=' . urlencode($token) . '&error=' . urlencode('Token geçersiz veya süre dolmuş.'));
        }

        Response::redirect('/login?toast=' . urlencode('Şifre güncellendi. Giriş yapabilirsin.'));
    }

    public function logout(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/?toast=csrf');
        }

        Auth::logoutUser();
        Response::redirect('/login');
    }
}
