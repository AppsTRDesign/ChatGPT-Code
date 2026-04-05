<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Services\GameService;

final class DashboardController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(): void
    {
        if (!Auth::adminCheck()) {
            Response::redirect('/admin/login');
        }

        $game = new GameService();

        View::render('admin/dashboard', [
            'config' => $this->config,
            'csrf' => Csrf::token(),
            'state' => $game->state(),
            'settings' => $game->getSettings(),
        ]);
    }

    public function settings(): void
    {
        if (!Auth::adminCheck()) {
            Response::redirect('/admin/login');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin?toast=G%C3%BCvenlik+do%C4%9Frulamas%C4%B1+ba%C5%9Far%C4%B1s%C4%B1z');
        }

        $payload = [
            'game_name' => trim((string) ($_POST['game_name'] ?? 'Noa Political Wars')),
            'tax_multiplier' => (string) max(0.1, min(5, (float) ($_POST['tax_multiplier'] ?? 1))),
            'training_cost' => (string) max(1, min(1000, (int) ($_POST['training_cost'] ?? 20))),
        ];

        (new GameService())->updateSettings($payload);
        Response::redirect('/admin?toast=' . urlencode('Ayarlar kaydedildi.'));
    }
}
