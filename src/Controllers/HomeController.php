<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
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
        $userId = Auth::userId();
        if (!$userId) {
            Response::redirect('/login');
        }

        $game = new GameService();
        $state = $game->dashboard($userId);

        View::render('game/index', [
            'config' => $this->config,
            'csrf' => Csrf::token(),
            'state' => $state,
        ]);
    }

    public function work(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/?toast=Yetkisiz');
        }

        $resource = (string) ($_POST['resource'] ?? 'gold');
        $result = (new GameService())->work($userId, $resource);
        Response::redirect('/?toast=' . urlencode($result['message']));
    }

    public function battle(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/?toast=Yetkisiz');
        }

        $result = (new GameService())->battle($userId);
        Response::redirect('/?toast=' . urlencode($result['message']));
    }

    public function upgrade(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/?toast=Yetkisiz');
        }

        $stat = (string) ($_POST['stat'] ?? 'strength');
        $result = (new GameService())->upgradeStat($userId, $stat);
        Response::redirect('/?toast=' . urlencode($result['message']));
    }
}
