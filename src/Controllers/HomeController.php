<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Services\GameService;

final class HomeController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(): void
    {
        $game = new GameService();
        $state = $game->state();

        View::render('game/index', [
            'config' => $this->config,
            'csrf' => Csrf::token(),
            'state' => $state,
        ]);
    }

    public function trainArmy(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/?toast=G%C3%BCvenlik+do%C4%9Frulamas%C4%B1+ba%C5%9Far%C4%B1s%C4%B1z');
        }

        $amount = (int) ($_POST['amount'] ?? 10);
        $result = (new GameService())->trainArmy($amount);

        Response::redirect('/?toast=' . urlencode($result['message']));
    }

    public function collectTaxes(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/?toast=G%C3%BCvenlik+do%C4%9Frulamas%C4%B1+ba%C5%9Far%C4%B1s%C4%B1z');
        }

        $result = (new GameService())->collectTaxes();
        Response::redirect('/?toast=' . urlencode($result['message']));
    }
}
