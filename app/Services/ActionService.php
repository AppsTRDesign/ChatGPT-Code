<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;
use RuntimeException;

final class ActionService
{
    public function __construct(
        private readonly PlayerService $playerService = new PlayerService(),
        private readonly MapModel $mapModel = new MapModel()
    ) {
    }

    public function execute(int $userId, int $regionId, string $action): array
    {
        $this->validateAction($userId, $regionId, $action);

        return match ($action) {
            'travel' => $this->performTravel($userId, $regionId),
            'visa_request', 'migrate', 'attack', 'invest' => throw new RuntimeException('Action not implemented yet'),
            default => throw new RuntimeException('invalid_action'),
        };
    }

    public function validateAction(int $userId, int $regionId, string $action): void
    {
        if ($userId <= 0) {
            throw new RuntimeException('unauthorized');
        }

        if (!in_array($action, ['travel', 'visa_request', 'migrate', 'attack', 'invest'], true)) {
            throw new RuntimeException('Invalid action');
        }

        $region = $this->mapModel->regionById($regionId);
        if (!$region) {
            throw new RuntimeException('Invalid region');
        }

        if ($action === 'travel') {
            $me = $this->playerService->me($userId);
            if (!$me) {
                throw new RuntimeException('unauthorized');
            }
            if ((int) $me['current_region_id'] === $regionId) {
                throw new RuntimeException('Already in this region');
            }
        }
    }

    public function performTravel(int $userId, int $regionId): array
    {
        $travel = $this->playerService->travel($userId, $regionId);
        return [
            'success' => true,
            'action' => 'travel',
            'message' => 'Travel started',
            'travel' => $travel['travel'],
        ];
    }
}
