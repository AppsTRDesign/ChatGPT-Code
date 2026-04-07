<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ActionService;
use RuntimeException;

final class ActionController
{
    public function __construct(private readonly ActionService $actionService = new ActionService())
    {
    }

    public function regionAction(Request $request): void
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $payload = $request->json();
        $regionId = (int) ($payload['region_id'] ?? 0);
        $action = trim((string) ($payload['action'] ?? ''));

        if ($regionId <= 0 || $action === '') {
            Response::json(['error' => 'invalid_request'], 422);
            return;
        }

        try {
            $result = $this->actionService->execute($userId, $regionId, $action);
            Response::json($result);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            $status = match ($error) {
                'unauthorized' => 401,
                'invalid_action', 'invalid_region', 'same_region', 'already_traveling', 'not_enough_energy', 'not_enough_coins', 'not_enough_resources' => 400,
                default => 400,
            };
            Response::json(['error' => $error], $status);
        }
    }
}
