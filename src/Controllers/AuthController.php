<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
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

    public function register(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/register?error=csrf');
        }

        $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $result = (new GameService())->register(
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            $ip
        );

        if (!$result['ok']) {
            Response::redirect('/register?error=' . urlencode($result['message']));
        }

        Auth::loginUser((int) $result['user_id']);
        Response::redirect('/?toast=' . urlencode('Hoş geldin, kayıt tamamlandı.'));
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/login?error=csrf');
        }

        $result = (new GameService())->login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
        if (!$result['ok']) {
            Response::redirect('/login?error=' . urlencode($result['message']));
        }

        Auth::loginUser((int) $result['user_id']);
        Response::redirect('/?toast=' . urlencode('Tekrar hoş geldin!'));
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
