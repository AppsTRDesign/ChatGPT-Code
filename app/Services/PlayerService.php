<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;
use App\Models\PlayerModel;
use RuntimeException;

final class PlayerService
{
    private const TRAVEL_SPEED_KMH = 800;

    public function __construct(
        private readonly PlayerModel $playerModel = new PlayerModel(),
        private readonly MapModel $mapModel = new MapModel()
    ) {
    }

    public function me(int $userId): ?array
    {
        $this->finalizeTravelIfDue($userId);
        $me = $this->playerModel->me($userId);
        if (!$me) {
            return null;
        }
        $me['active_travel'] = $this->travelStatus($userId);
        return $me;
    }

    public function travel(int $userId, int $toRegionId): array
    {
        $me = $this->playerModel->me($userId);
        if (!$me) {
            throw new RuntimeException('Player profile not found');
        }

        if ($this->playerModel->activeTravel($userId)) {
            throw new RuntimeException('already_traveling');
        }

        $fromRegionId = (int) $me['current_region_id'];
        if ($fromRegionId === $toRegionId) {
            throw new RuntimeException('same_region');
        }

        $fromRegion = $this->mapModel->regionById($fromRegionId);
        $destination = $this->mapModel->regionById($toRegionId);
        if (!$fromRegion || !$destination) {
            throw new RuntimeException('invalid_region');
        }

        $distance = $this->distanceKm((float) $fromRegion['lat'], (float) $fromRegion['lng'], (float) $destination['lat'], (float) $destination['lng']);
        $durationSeconds = max(5, (int) round(($distance / self::TRAVEL_SPEED_KMH) * 3600));

        $travel = $this->playerModel->createTravel($userId, $fromRegionId, $toRegionId, $distance, $durationSeconds);

        return [
            'status' => 'traveling',
            'travel' => $travel,
        ];
    }

    public function travelStatus(int $userId): ?array
    {
        $travel = $this->playerModel->activeTravel($userId);
        if (!$travel) {
            return null;
        }

        $now = time();
        $end = strtotime((string) $travel['end_time']);
        return [
            'from_region_id' => (int) $travel['from_region_id'],
            'to_region_id' => (int) $travel['to_region_id'],
            'distance_km' => (float) $travel['distance_km'],
            'duration_seconds' => (int) $travel['duration_seconds'],
            'start_time' => (string) $travel['start_time'],
            'end_time' => (string) $travel['end_time'],
            'remaining_seconds' => max(0, $end - $now),
        ];
    }

    public function finalizeTravelIfDue(int $userId): void
    {
        $travel = $this->playerModel->activeTravel($userId);
        if (!$travel) {
            return;
        }

        if (strtotime((string) $travel['end_time']) > time()) {
            return;
        }

        $destination = $this->mapModel->regionById((int) $travel['to_region_id']);
        if (!$destination) {
            return;
        }

        $this->playerModel->updateLocation($userId, (int) $travel['to_region_id'], (int) $destination['country_id']);
        $this->playerModel->completeTravel($userId);
        $this->playerModel->logTravel($userId, (int) $travel['from_region_id'], (int) $travel['to_region_id'], 'completed');
    }

    public function travelHistory(int $userId): array
    {
        $this->finalizeTravelIfDue($userId);
        return $this->playerModel->travelHistory($userId);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
