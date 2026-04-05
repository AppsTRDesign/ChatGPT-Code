<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;

final class LoginController
{
    public function __construct(private readonly array $config)
    {
    }

    public function show(): void
    {
        View::render('admin/login', [
            'config' => $this->config,
            'csrf' => Csrf::token(),
        ]);
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/login?error=csrf');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::attemptAdmin($username, $password)) {
            Response::redirect('/admin');
        }

        Response::redirect('/admin/login?error=credentials');
    }

    public function logout(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/login?error=csrf');
        }

        Auth::logoutAdmin();
        Response::redirect('/admin/login');
    }
}
