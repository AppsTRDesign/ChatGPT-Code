<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PlayerModel
{
    public function createProfile(int $userId, int $regionId, int $countryRegionId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO player_profiles(user_id,current_region_id,current_country_region_id,level,xp,xp_to_next,gold,coins,instant_energy,max_instant_energy,total_energy,last_energy_update,created_at)
             VALUES(:user_id,:current_region_id,:current_country_region_id,1,0,100,1000,100000000,300,300,100000,NOW(),NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'current_region_id' => $regionId,
            'current_country_region_id' => $countryRegionId,
        ]);
    }

    public function createCitizenship(int $userId, int $countryRegionId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO citizenships(user_id,country_region_id,is_homeland,created_at) VALUES(:user_id,:country_region_id,1,NOW())'
        );
        $stmt->execute(['user_id' => $userId, 'country_region_id' => $countryRegionId]);
    }

    public function me(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id,u.username,u.email,p.level,p.xp,p.xp_to_next,p.gold,p.coins,p.instant_energy,p.max_instant_energy,p.total_energy,p.last_energy_update,p.current_region_id,p.current_country_region_id,
                    r.name AS current_region_name,r.lat AS current_region_lat,r.lng AS current_region_lng,c.name AS current_country_name,c.color AS current_country_color,
                    ch.country_region_id AS citizenship_country_region_id,cc.name AS citizenship_country_name
             FROM users u
             JOIN player_profiles p ON p.user_id = u.id
             JOIN regions r ON r.id = p.current_region_id
             JOIN regions c ON c.id = p.current_country_region_id
             LEFT JOIN citizenships ch ON ch.user_id = u.id AND ch.is_homeland = 1
             LEFT JOIN regions cc ON cc.id = ch.country_region_id
             WHERE u.id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function updateLocation(int $userId, int $regionId, int $countryRegionId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE player_profiles SET current_region_id = :region_id, current_country_region_id = :country_region_id WHERE user_id = :user_id'
        );
        $stmt->execute(['region_id' => $regionId, 'country_region_id' => $countryRegionId, 'user_id' => $userId]);
    }

    public function updateEnergy(int $userId, int $instantEnergy, string $timestamp): void
    {
        $stmt = Database::connection()->prepare('UPDATE player_profiles SET instant_energy=:instant_energy,last_energy_update=:ts WHERE user_id=:user_id');
        $stmt->execute(['instant_energy' => $instantEnergy, 'ts' => $timestamp, 'user_id' => $userId]);
    }

    public function spendForTravel(int $userId, int $energyCost, float $coinCost): bool
    {
        $stmt = Database::connection()->prepare('UPDATE player_profiles SET instant_energy = instant_energy - :energy_cost, total_energy = total_energy - :energy_cost, coins = coins - :coin_cost WHERE user_id = :user_id AND instant_energy >= :energy_cost AND total_energy >= :energy_cost AND coins >= :coin_cost');
        $stmt->execute(['energy_cost' => $energyCost, 'coin_cost' => $coinCost, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function buyEnergyWithGold(int $userId, int $goldCost, int $energyAmount): bool
    {
        $stmt = Database::connection()->prepare('UPDATE player_profiles SET gold = gold - :gold_cost, total_energy = total_energy + :energy_amount WHERE user_id = :user_id AND gold >= :gold_cost');
        $stmt->execute([
            'gold_cost' => $goldCost,
            'energy_amount' => $energyAmount,
            'user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function addXp(int $userId, int $xpGain): void
    {
        $stmt = Database::connection()->prepare('UPDATE player_profiles SET xp = xp + :xp WHERE user_id = :user_id');
        $stmt->execute(['xp' => $xpGain, 'user_id' => $userId]);
    }

    public function applyLevelUps(int $userId): void
    {
        while (true) {
            $stmt = Database::connection()->prepare('SELECT level,xp,xp_to_next FROM player_profiles WHERE user_id=:user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();
            if (!$row) {
                return;
            }
            $level = (int) $row['level'];
            $xp = (int) $row['xp'];
            $xpToNext = (int) $row['xp_to_next'];

            if ($xp < $xpToNext) {
                return;
            }

            $newLevel = $level + 1;
            $newXp = $xp - $xpToNext;
            $newXpToNext = $newLevel * 100;
            $up = Database::connection()->prepare('UPDATE player_profiles SET level=:level,xp=:xp,xp_to_next=:xp_to_next WHERE user_id=:user_id');
            $up->execute(['level' => $newLevel, 'xp' => $newXp, 'xp_to_next' => $newXpToNext, 'user_id' => $userId]);
        }
    }

    public function travelHistory(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.id,t.status,t.started_at,t.completed_at,fr.name AS from_region,tr.name AS to_region
             FROM travel_logs t
             JOIN regions fr ON fr.id = t.from_region_id
             JOIN regions tr ON tr.id = t.to_region_id
             WHERE t.user_id = :user_id
             ORDER BY t.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function transferPopulation(int $fromRegionId, int $toRegionId): void
    {
        if ($fromRegionId === $toRegionId) {
            return;
        }
        $dec = Database::connection()->prepare('UPDATE regions SET population = GREATEST(population - 1, 0) WHERE id = :id');
        $inc = Database::connection()->prepare('UPDATE regions SET population = population + 1 WHERE id = :id');
        $dec->execute(['id' => $fromRegionId]);
        $inc->execute(['id' => $toRegionId]);
    }

    public function addCountryTreasuryMoney(int $countryRegionId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }
        $stmt = Database::connection()->prepare('UPDATE country_economy SET treasury_state_money = treasury_state_money + :amount WHERE country_region_id = :id');
        $stmt->execute(['amount' => (int) round($amount), 'id' => $countryRegionId]);
    }

    public function logTravel(int $userId, int $fromRegionId, int $toRegionId, string $status): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO travel_logs(user_id,from_region_id,to_region_id,status,started_at,completed_at)
            VALUES(:user_id,:from_region_id,:to_region_id,:status,NOW(),NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'status' => $status,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function activeTravel(int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT pt.*, UNIX_TIMESTAMP(pt.start_time) AS start_ts, UNIX_TIMESTAMP(pt.end_time) AS end_ts FROM player_travel pt WHERE pt.user_id = :user_id AND pt.status IN ("traveling","returning") LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function createTravel(int $userId, int $fromRegionId, int $toRegionId, float $distanceKm, int $costCoins, int $durationSeconds, string $status = "traveling", float $startProgress = 0.0, float $endProgress = 1.0): array
    {
        $durationSeconds = max(5, $durationSeconds);

        $stmt = Database::connection()->prepare(
            'INSERT INTO player_travel(user_id,from_region_id,to_region_id,distance_km,cost_coins,duration_seconds,start_time,end_time,status,start_progress,end_progress,created_at)
             VALUES(:user_id,:from_region_id,:to_region_id,:distance_km,:cost_coins,:duration_seconds,NOW(),DATE_ADD(NOW(), INTERVAL :duration_seconds SECOND),:status,:start_progress,:end_progress,NOW())
             ON DUPLICATE KEY UPDATE from_region_id=VALUES(from_region_id),to_region_id=VALUES(to_region_id),distance_km=VALUES(distance_km),cost_coins=VALUES(cost_coins),duration_seconds=VALUES(duration_seconds),start_time=VALUES(start_time),end_time=VALUES(end_time),status=VALUES(status),start_progress=VALUES(start_progress),end_progress=VALUES(end_progress)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'distance_km' => $distanceKm,
            'cost_coins' => $costCoins,
            'duration_seconds' => $durationSeconds,
            'status' => $status,
            'start_progress' => $startProgress,
            'end_progress' => $endProgress,
        ]);

        $travel = $this->activeTravel($userId);
        return [
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'distance_km' => round($distanceKm, 2),
            'cost_coins' => $costCoins,
            'duration_seconds' => $durationSeconds,
            'start_time' => (string) ($travel['start_time'] ?? ''),
            'end_time' => (string) ($travel['end_time'] ?? ''),
            'status' => (string) ($travel['status'] ?? $status),
            'start_progress' => (float) ($travel['start_progress'] ?? $startProgress),
            'end_progress' => (float) ($travel['end_progress'] ?? $endProgress),
        ];
    }


    public function markReturning(int $userId, int $durationSeconds, float $startProgress): ?array
    {
        $travel = $this->activeTravel($userId);
        if (!$travel) {
            return null;
        }

        $stmt = Database::connection()->prepare('UPDATE player_travel SET status="returning", duration_seconds=:duration_seconds, start_time=NOW(), end_time=DATE_ADD(NOW(), INTERVAL :duration_seconds SECOND), start_progress=:start_progress, end_progress=0 WHERE user_id=:user_id AND status IN ("traveling","returning")');
        $stmt->execute([
            'duration_seconds' => max(5, $durationSeconds),
            'start_progress' => $startProgress,
            'user_id' => $userId,
        ]);

        return $this->activeTravel($userId);
    }

    public function completeTravel(int $userId): ?array
    {
        $travel = $this->activeTravel($userId);
        if (!$travel) {
            return null;
        }

        $stmt = Database::connection()->prepare('UPDATE player_travel SET status="completed" WHERE user_id = :user_id AND status IN ("traveling","returning")');
        $stmt->execute(['user_id' => $userId]);
        return $travel;
    }
}
