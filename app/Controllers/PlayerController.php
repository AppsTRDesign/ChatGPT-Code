<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PlayerService;
use RuntimeException;

final class PlayerController
{
    public function __construct(private readonly PlayerService $playerService = new PlayerService())
    {
    }

    public function me(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $me = $this->playerService->me($userId);
        if (!$me) {
            Response::json(['error' => 'Player not found'], 404);
            return;
        }
        Response::json(['data' => $me]);
    }

    public function travel(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $payload = $request->json();
        $toRegionId = (int) ($payload['to_region_id'] ?? 0);
        if ($toRegionId <= 0) {
            Response::json(['error' => 'to_region_id is required'], 422);
            return;
        }

        try {
            $result = $this->playerService->travel($userId, $toRegionId);
            Response::json(['data' => $result]);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function travelHistory(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        Response::json(['data' => $this->playerService->travelHistory($userId)]);
    }

    public function cancelTravel(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $travel = $this->playerService->cancelTravel($userId);
        if (!$travel) {
            Response::json(['error' => 'No active travel'], 400);
            return;
        }
        Response::json(['success' => true, 'travel' => $travel]);
    }

    public function buyEnergy(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        try {
            $result = $this->playerService->buyEnergy($userId);
            Response::json(['success' => true, 'data' => $result]);
        } catch (RuntimeException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
