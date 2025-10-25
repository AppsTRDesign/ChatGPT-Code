<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Session;
use App\Support\View;

abstract class Controller
{
    protected Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    protected function view(string $template, array $data = []): string
    {
        $user = $this->session->user();
        $flash = $this->session->consumeFlash();
        return View::render($template, array_merge($data, compact('user', 'flash')));
    }

    protected function requireRole(string $role): void
    {
        $user = $this->session->user();
        if (!$user || $user['role'] !== $role) {
            header('Location: /login');
            exit;
        }
    }
}
