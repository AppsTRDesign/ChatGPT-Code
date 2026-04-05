<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
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
        $userId = Auth::userId();
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        Response::json(['ok' => true, 'data' => (new GameService())->dashboard($userId)]);
    }

    public function work(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $resource = (string) ($_POST['resource'] ?? 'gold');
        $result = (new GameService())->work($userId, $resource);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function battle(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $result = (new GameService())->battle($userId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function upgrade(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $stat = (string) ($_POST['stat'] ?? 'strength');
        $result = (new GameService())->upgradeStat($userId, $stat);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function marketCreate(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $resourceId = (int) ($_POST['resource_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $price = (float) ($_POST['price_per_unit'] ?? 0);
        $result = (new GameService())->createMarketOffer($userId, $resourceId, $quantity, $price);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function marketBuy(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $result = (new GameService())->buyMarketOffer($userId, $offerId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }
}
