<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Csrf;
use App\Core\Response;
use App\Services\GameService;

final class GameController
{
    public function __construct(private readonly array $config)
    {
    }

    public function state(): void
    {
        $state = (new GameService())->state();
        Response::json([
            'ok' => true,
            'data' => $state,
        ]);
    }

    public function train(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'CSRF token invalid.'], 422);
            return;
        }

        $amount = (int) ($_POST['amount'] ?? 10);
        $result = (new GameService())->trainArmy($amount);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function collect(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'CSRF token invalid.'], 422);
            return;
        }

        $result = (new GameService())->collectTaxes();
        Response::json($result, $result['ok'] ? 200 : 422);
    }
}
