<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Config;
use App\Support\DeviceProfiler;
use App\Support\Session;
use App\Support\View;

abstract class Controller
{
    protected Session $session;
    protected array $deviceProfile = [];

    public function __construct(Session $session)
    {
        $this->session = $session;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $this->deviceProfile = (new DeviceProfiler($userAgent))->toArray();
    }

    protected function view(string $template, array $data = []): string
    {
        $user = $this->session->user();
        $flash = $this->session->consumeFlash();
        $app = Config::all('app');
        $layout = $data['layout'] ?? null;
        unset($data['layout']);

        return View::render($template, array_merge($data, [
            'user' => $user,
            'flash' => $flash,
            'app' => $app,
            'device' => $this->deviceProfile,
        ]), $layout);
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
