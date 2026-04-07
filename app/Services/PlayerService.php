<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;
use App\Models\PlayerModel;
use RuntimeException;

final class PlayerService
{
    private const TRAVEL_SPEED_KMH = 3200;
    private const TRAVEL_ENERGY_COST = 10;
    private const BUY_ENERGY_GOLD_COST = 1000;
    private const BUY_ENERGY_TOTAL_AMOUNT = 100000;

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
        $travel = $this->travelStatus($userId);
        $me['is_traveling'] = $travel !== null;
        $me['travel'] = $travel;
        $me['active_travel'] = $travel;
        $me['energy'] = (int) $me['instant_energy'];
        $me['max_energy'] = (int) $me['max_instant_energy'];
        $me['current_position'] = $travel['current_position'] ?? [
            'lat' => (float) $me['current_region_lat'],
            'lng' => (float) $me['current_region_lng'],
        ];
        if ($travel) {
            $me['status'] = $travel['status'];
            $me['from_region_id'] = $travel['from_region_id'];
            $me['to_region_id'] = $travel['to_region_id'];
            $me['remaining_seconds'] = $travel['remaining_seconds'];
            $me['progress'] = $travel['progress'];
        }

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
        $airportCount = (int) ($fromRegion['airport_building_count'] ?? 100);
        $effectiveSpeed = self::TRAVEL_SPEED_KMH * (1 + log($airportCount + 1) * 0.25);
        $durationSeconds = max(5, (int) round(($distance / max(1, $effectiveSpeed)) * 3600));
        $coinCost = max(10, (int) ceil($distance * 0.5));

        if ((int) $me['instant_energy'] < self::TRAVEL_ENERGY_COST) {
            throw new RuntimeException('not_enough_energy');
        }
        if ((float) $me['coins'] < $coinCost) {
            throw new RuntimeException('Not enough coins');
        }

        $spent = $this->playerModel->spendForTravel($userId, self::TRAVEL_ENERGY_COST, $coinCost);
        if (!$spent) {
            throw new RuntimeException('Not enough coins');
        }

        $this->playerModel->createTravel($userId, $fromRegionId, $toRegionId, $distance, $coinCost, $durationSeconds);
        $travel = $this->travelStatus($userId);
        if ($travel === null) {
            throw new RuntimeException('travel_failed');
        }
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
        $start = isset($travel['start_ts']) ? (int) $travel['start_ts'] : (strtotime((string) $travel['start_time']) ?: time());
        $end = isset($travel['end_ts']) ? (int) $travel['end_ts'] : (strtotime((string) $travel['end_time']) ?: $start);
        $remaining = max(0, $end - $now);
        $denom = max(1, $end - $start);
        $ratio = min(1, max(0, ($now - $start) / $denom));
        $startP = (float) ($travel['start_progress'] ?? 0);
        $endP = (float) ($travel['end_progress'] ?? 1);
        $progress = $startP + (($endP - $startP) * $ratio);
        $fromRegion = $this->mapModel->regionById((int) $travel['from_region_id']);
        $toRegion = $this->mapModel->regionById((int) $travel['to_region_id']);
        $fromLat = (float) ($fromRegion['lat'] ?? 0);
        $fromLng = (float) ($fromRegion['lng'] ?? 0);
        $toLat = (float) ($toRegion['lat'] ?? 0);
        $toLng = (float) ($toRegion['lng'] ?? 0);
        $currentLat = $fromLat + (($toLat - $fromLat) * $progress);
        $currentLng = $fromLng + (($toLng - $fromLng) * $progress);
        return [
            'from_region_id' => (int) $travel['from_region_id'],
            'to_region_id' => (int) $travel['to_region_id'],
            'distance_km' => (float) $travel['distance_km'],
            'cost_coins' => (int) ($travel['cost_coins'] ?? 0),
            'duration_seconds' => (int) $travel['duration_seconds'],
            'start_time' => (string) $travel['start_time'],
            'end_time' => (string) $travel['end_time'],
            'remaining_seconds' => $remaining,
            'status' => (string) $travel['status'],
            'start_progress' => $startP,
            'end_progress' => $endP,
            'progress' => round($progress, 6),
            'progress_percent' => (int) round($progress * 100),
            'current_position' => [
                'lat' => $currentLat,
                'lng' => $currentLng,
            ],
        ];
    }

    public function finalizeTravelIfDue(int $userId): void
    {
        $travel = $this->playerModel->activeTravel($userId);
        if (!$travel) {
            return;
        }

        $endTs = isset($travel['end_ts']) ? (int) $travel['end_ts'] : (strtotime((string) $travel['end_time']) ?: time());
        if ($endTs > time()) {
            return;
        }

        $finalRegionId = (string) $travel['status'] === 'returning'
            ? (int) $travel['from_region_id']
            : (int) $travel['to_region_id'];
        $destination = $this->mapModel->regionById($finalRegionId);
        if (!$destination) {
            return;
        }

        $this->playerModel->updateLocation($userId, $finalRegionId, (int) ($destination['owner_region_id'] ?? $destination['id']));
        $this->playerModel->completeTravel($userId);
        $xpGain = (int) max(1, floor(((float) $travel['distance_km']) / 10));
        $this->playerModel->addXp($userId, $xpGain);
        $this->playerModel->applyLevelUps($userId);
        $this->playerModel->logTravel($userId, (int) $travel['from_region_id'], $finalRegionId, 'completed');
    }


    public function cancelTravel(int $userId): ?array
    {
        $travel = $this->playerModel->activeTravel($userId);
        if (!$travel) {
            return null;
        }

        $startTs = isset($travel['start_ts']) ? (int) $travel['start_ts'] : (strtotime((string) $travel['start_time']) ?: time());
        $now = time();
        $elapsed = max(1, $now - $startTs);
        $duration = max(1, (int) $travel['duration_seconds']);
        $ratio = min(1, $elapsed / $duration);
        $startProgress = (float) ($travel['start_progress'] ?? 0) + ((float) (($travel['end_progress'] ?? 1) - ($travel['start_progress'] ?? 0)) * $ratio);
        $returnDuration = max(5, $elapsed);
        $this->playerModel->markReturning($userId, $returnDuration, $startProgress);
        return $this->travelStatus($userId);
    }

    public function travelHistory(int $userId): array
    {
        $this->finalizeTravelIfDue($userId);
        return $this->playerModel->travelHistory($userId);
    }

    public function buyEnergy(int $userId): array
    {
        $ok = $this->playerModel->buyEnergyWithGold($userId, self::BUY_ENERGY_GOLD_COST, self::BUY_ENERGY_TOTAL_AMOUNT);
        if (!$ok) {
            throw new RuntimeException('not_enough_gold');
        }
        $profile = $this->me($userId);
        if (!$profile) {
            throw new RuntimeException('Player profile not found');
        }
        return [
            'gold_spent' => self::BUY_ENERGY_GOLD_COST,
            'total_energy_added' => self::BUY_ENERGY_TOTAL_AMOUNT,
            'instant_energy' => (int) $profile['instant_energy'],
            'max_instant_energy' => (int) $profile['max_instant_energy'],
            'total_energy' => (int) $profile['total_energy'],
            'gold' => (float) $profile['gold'],
        ];
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
        $energy = (int) ($me['instant_energy'] ?? 0);
        $maxEnergy = (int) ($me['max_instant_energy'] ?? 300);
        $lastUpdate = $me['last_energy_update'] ? strtotime((string) $me['last_energy_update']) : time();
        $now = time();

        if ($energy >= $maxEnergy) {
            $this->playerModel->updateEnergy($userId, $maxEnergy, date('Y-m-d H:i:s', $now));
            return;
        }

        $elapsed = max(0, $now - $lastUpdate);
        $regenPoints = intdiv($elapsed, 2);
        if ($regenPoints <= 0) {
            return;
        }

        $newEnergy = min($maxEnergy, $energy + $regenPoints);
        $usedSeconds = $regenPoints * 2;
        $newTimestamp = date('Y-m-d H:i:s', $lastUpdate + $usedSeconds);
        $this->playerModel->updateEnergy($userId, $newEnergy, $newTimestamp);
    }
}
