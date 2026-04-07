<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;
use App\Models\PlayerModel;
use RuntimeException;

final class PlayerService
{
    public function __construct(
        private readonly PlayerModel $playerModel = new PlayerModel(),
        private readonly MapModel $mapModel = new MapModel()
    ) {
    }

    public function me(int $userId): ?array
    {
        return $this->playerModel->me($userId);
    }

    public function travel(int $userId, int $toRegionId): array
    {
        $me = $this->playerModel->me($userId);
        if (!$me) {
            throw new RuntimeException('Player profile not found');
        }

        $fromRegionId = (int) $me['current_region_id'];
        if ($fromRegionId === $toRegionId) {
            throw new RuntimeException('Destination must be different from current region');
        }

        $destination = $this->mapModel->regionById($toRegionId);
        if (!$destination) {
            throw new RuntimeException('Destination region not found');
        }

        $travelId = $this->playerModel->logTravel($userId, $fromRegionId, $toRegionId, 'completed');
        $this->playerModel->updateLocation($userId, $toRegionId, (int) $destination['country_id']);

        return [
            'travel_log_id' => $travelId,
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'status' => 'completed',
        ];
    }

    public function travelHistory(int $userId): array
    {
        return $this->playerModel->travelHistory($userId);
    }
}
