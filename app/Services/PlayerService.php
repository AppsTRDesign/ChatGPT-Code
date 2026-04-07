<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;
use App\Models\PlayerModel;
use RuntimeException;

final class PlayerService
{
    private const TRAVEL_SPEED_KMH = 800;
    private const TRAVEL_ENERGY_COST = 10;

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

        $this->regenerateEnergy($userId, $me);
        $me = $this->playerModel->me($userId);
        $me['active_travel'] = $this->travelStatus($userId);
        return $me;
    }

    public function travel(int $userId, int $toRegionId): array
    {
        $me = $this->playerModel->me($userId);
        if (!$me) {
            throw new RuntimeException('Player profile not found');
        }

        $this->regenerateEnergy($userId, $me);
        $me = $this->playerModel->me($userId);

        if ($this->playerModel->activeTravel($userId)) {
            throw new RuntimeException('Already traveling');
        }

        $fromRegionId = (int) $me['current_region_id'];
        if ($fromRegionId === $toRegionId) {
            throw new RuntimeException('Already in this region');
        }

        $fromRegion = $this->mapModel->regionById($fromRegionId);
        $destination = $this->mapModel->regionById($toRegionId);
        if (!$fromRegion || !$destination) {
            throw new RuntimeException('Invalid region');
        }

        $distance = $this->distanceKm((float) $fromRegion['lat'], (float) $fromRegion['lng'], (float) $destination['lat'], (float) $destination['lng']);
        $durationSeconds = max(5, (int) round(($distance / self::TRAVEL_SPEED_KMH) * 3600));
        $coinCost = max(10, (int) ceil($distance * 0.5));

        if ((int) $me['energy'] < self::TRAVEL_ENERGY_COST) {
            throw new RuntimeException('not_enough_energy');
        }
        if ((float) $me['coins'] < $coinCost) {
            throw new RuntimeException('Not enough coins');
        }

        $spent = $this->playerModel->spendForTravel($userId, self::TRAVEL_ENERGY_COST, $coinCost);
        if (!$spent) {
            throw new RuntimeException('Not enough coins');
        }

        $travel = $this->playerModel->createTravel($userId, $fromRegionId, $toRegionId, $distance, $coinCost, $durationSeconds);
        $travel['energy_cost'] = self::TRAVEL_ENERGY_COST;
        $travel['cost_coins'] = $coinCost;

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
        $xpGain = (int) max(1, floor(((float) $travel['distance_km']) / 10));
        $this->playerModel->addXp($userId, $xpGain);
        $this->playerModel->applyLevelUps($userId);
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

    private function regenerateEnergy(int $userId, array $me): void
    {
        $energy = (int) $me['energy'];
        $maxEnergy = (int) ($me['max_energy'] ?? 100);
        $lastUpdate = $me['last_energy_update'] ? strtotime((string) $me['last_energy_update']) : time();
        $now = time();

        if ($energy >= $maxEnergy) {
            $this->playerModel->updateEnergy($userId, $maxEnergy, date('Y-m-d H:i:s', $now));
            return;
        }

        $elapsed = max(0, $now - $lastUpdate);
        $regenPoints = intdiv($elapsed, 300);
        if ($regenPoints <= 0) {
            return;
        }

        $newEnergy = min($maxEnergy, $energy + $regenPoints);
        $usedSeconds = $regenPoints * 300;
        $newTimestamp = date('Y-m-d H:i:s', $lastUpdate + $usedSeconds);
        $this->playerModel->updateEnergy($userId, $newEnergy, $newTimestamp);
    }
}
