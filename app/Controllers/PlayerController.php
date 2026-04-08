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
            Response::json(['error' => 'player_not_found', 'message' => tr('errors.player_not_found')], 404);
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
            Response::json(['error' => 'invalid_request', 'message' => tr('errors.invalid_request')], 422);
            return;
        }

        try {
            $result = $this->playerService->travel($userId, $toRegionId);
            Response::json(['data' => $result]);
        } catch (RuntimeException $e) {
            $code = $this->normalizeErrorCode($e->getMessage());
            Response::json(['error' => $code, 'message' => tr('errors.' . $code)], 400);
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
            Response::json(['error' => 'no_active_travel', 'message' => tr('errors.no_active_travel')], 400);
            return;
        }
        Response::json(['success' => true, 'message' => tr('toast.travel_reversed'), 'travel' => $travel]);
    }

    public function buyEnergy(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        try {
            $payload = $request->json();
            $energyAmount = (int) ($payload['energy_amount'] ?? 100000);
            $result = $this->playerService->buyEnergy($userId, $energyAmount);
            Response::json(['success' => true, 'message' => tr('toast.buy_energy_success'), 'data' => $result]);
        } catch (RuntimeException $e) {
            $code = $this->normalizeErrorCode($e->getMessage());
            Response::json(['error' => $code, 'message' => tr('errors.' . $code)], 400);
        }
    }

    public function changeNation(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $payload = $request->json();
        $countryId = (int) ($payload['country_id'] ?? 0);
        if ($countryId <= 0) {
            Response::json(['error' => 'invalid_request', 'message' => tr('errors.invalid_request')], 422);
            return;
        }
        try {
            $result = $this->playerService->changeNation($userId, $countryId);
            Response::json(['success' => true, 'message' => tr('toast.success'), 'data' => $result]);
        } catch (RuntimeException $e) {
            $code = $this->normalizeErrorCode($e->getMessage());
            Response::json(['error' => $code, 'message' => tr('errors.' . $code)], 400);
        }
    }

    public function startStat(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $payload = $request->json();
        $stat = (string) ($payload['stat'] ?? '');
        $mode = (string) ($payload['mode'] ?? 'coins');
        try {
            $result = $this->playerService->startStatDevelopment($userId, $stat, $mode);
            Response::json(['success' => true, 'data' => $result]);
        } catch (RuntimeException $e) {
            $code = $this->normalizeErrorCode($e->getMessage());
            Response::json(['error' => $code, 'message' => tr('errors.' . $code)], 400);
        }
    }

    public function notifications(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        Response::json(['data' => $this->playerService->notifications($userId)]);
    }

    public function readNotifications(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        Response::json(['success' => true, 'data' => $this->playerService->markNotificationsRead($userId)]);
    }

    public function stopStat(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $ok = $this->playerService->stopStatDevelopment($userId);
        if (!$ok) {
            Response::json(['error' => 'no_active_stat', 'message' => tr('errors.invalid_request')], 400);
            return;
        }
        Response::json(['success' => true, 'message' => tr('toast.success')]);
    }

    public function statPreview(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $stat = (string) ($_GET['stat'] ?? '');
        try {
            $data = $this->playerService->statPreview($userId, $stat);
            Response::json(['data' => $data]);
        } catch (RuntimeException $e) {
            $code = $this->normalizeErrorCode($e->getMessage());
            Response::json(['error' => $code, 'message' => tr('errors.' . $code)], 400);
        }
    }

    private function normalizeErrorCode(string $raw): string
    {
        return match ($raw) {
            'not_enough_energy' => 'not_enough_energy',
            'Not enough coins', 'not_enough_coins' => 'not_enough_coins',
            'Already traveling', 'already_traveling' => 'already_traveling',
            'Invalid region', 'invalid_region' => 'invalid_region',
            'travel_failed' => 'travel_failed',
            'not_enough_gold' => 'not_enough_gold',
            'nation_cooldown' => 'nation_cooldown',
            'stat_already_active' => 'stat_already_active',
            default => 'invalid_request',
        };
    }
}
