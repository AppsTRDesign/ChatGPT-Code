<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PlayerModel
{
    public function createProfile(int $userId, int $regionId, int $countryRegionId, int $nationCountryId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO player_profiles(user_id,current_region_id,current_country_region_id,nation_country_id,nation_changed_at,strength,education,endurance,active_stat,stat_mode,stat_started_at,stat_finish_time,level,xp,xp_to_next,gold,coins,instant_energy,max_instant_energy,total_energy,last_energy_update,created_at)
             VALUES(:user_id,:current_region_id,:current_country_region_id,:nation_country_id,NOW(),0,0,0,NULL,NULL,NULL,NULL,1,0,100,1000,100000000,300,300,100000,NOW(),NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'current_region_id' => $regionId,
            'current_country_region_id' => $countryRegionId,
            'nation_country_id' => $nationCountryId,
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
            'SELECT u.id,u.username,u.email,p.level,p.xp,p.xp_to_next,p.gold,p.coins,p.instant_energy,p.max_instant_energy,p.total_energy,p.last_energy_update,p.current_region_id,p.current_country_region_id,p.nation_country_id,p.nation_changed_at,p.strength,p.education,p.endurance,p.active_stat,p.stat_mode,p.stat_started_at,p.stat_finish_time,
                    r.name AS current_region_name,r.lat AS current_region_lat,r.lng AS current_region_lng,c.name AS current_country_name,cv.color AS current_country_color,
                    ch.country_region_id AS citizenship_country_region_id,cc.name AS citizenship_country_name,nc.name AS nation_name,nc.flag_url AS nation_flag_url,nc.iso_code AS nation_iso_code,nc.color AS nation_color
             FROM users u
             JOIN player_profiles p ON p.user_id = u.id
             JOIN regions r ON r.id = p.current_region_id
             JOIN regions c ON c.id = p.current_country_region_id
             LEFT JOIN country_visuals cv ON cv.country_region_id = c.id
             LEFT JOIN citizenships ch ON ch.user_id = u.id AND ch.is_homeland = 1
             LEFT JOIN regions cc ON cc.id = ch.country_region_id
             LEFT JOIN countries nc ON nc.id = p.nation_country_id
             WHERE u.id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function changeNation(int $userId, int $countryId, int $goldCost): bool
    {
        $stmt = Database::connection()->prepare('UPDATE player_profiles SET nation_country_id=:country_id, nation_changed_at=NOW(), gold=gold-:gold_cost WHERE user_id=:user_id AND gold>=:gold_cost AND (nation_changed_at IS NULL OR nation_changed_at <= DATE_SUB(NOW(), INTERVAL 30 DAY))');
        $stmt->execute(['country_id' => $countryId, 'gold_cost' => $goldCost, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
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

    public function startStatDevelopment(int $userId, string $stat, string $mode, int $cost, int $durationSeconds): bool
    {
        $currencyField = $mode === 'gold' ? 'gold' : 'coins';
        $stmt = Database::connection()->prepare("UPDATE player_profiles SET {$currencyField} = {$currencyField} - :cost, active_stat=:stat, stat_mode=:mode, stat_started_at=NOW(), stat_finish_time=DATE_ADD(NOW(), INTERVAL :duration SECOND) WHERE user_id=:user_id AND active_stat IS NULL AND {$currencyField} >= :cost");
        $stmt->execute(['cost' => $cost, 'stat' => $stat, 'mode' => $mode, 'duration' => max(1,$durationSeconds), 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function completeStatIfDue(int $userId): ?array
    {
        $row = $this->me($userId);
        if (!$row || empty($row['active_stat']) || empty($row['stat_finish_time'])) {
            return null;
        }
        if ((strtotime((string)$row['stat_finish_time']) ?: time()) > time()) {
            return null;
        }
        $stat = (string) $row['active_stat'];
        if (!in_array($stat, ['strength','education','endurance'], true)) {
            return null;
        }
        $stmt = Database::connection()->prepare("UPDATE player_profiles SET {$stat} = {$stat} + 1, active_stat=NULL, stat_mode=NULL, stat_started_at=NULL, stat_finish_time=NULL WHERE user_id=:user_id");
        $stmt->execute(['user_id' => $userId]);
        $this->addNotification($userId, 'stat_complete', ['stat' => $stat, 'new_level' => (int)$row[$stat] + 1]);
        return ['stat' => $stat, 'new_level' => (int)$row[$stat] + 1];
    }

    public function addNotification(int $userId, string $type, array $data): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO notifications(user_id,type,data,is_read,created_at) VALUES(:user_id,:type,:data,0,NOW())');
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'data' => json_encode($data)]);
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
        $stmt = Database::connection()->prepare(
            'SELECT pt.*, UNIX_TIMESTAMP(pt.start_time) AS start_ts, UNIX_TIMESTAMP(pt.end_time) AS end_ts,
                    fr.name AS from_region_name, tr.name AS to_region_name
             FROM player_travel pt
             JOIN regions fr ON fr.id = pt.from_region_id
             JOIN regions tr ON tr.id = pt.to_region_id
             WHERE pt.user_id = :user_id AND pt.status IN ("traveling","returning")
             LIMIT 1'
        );
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

    public function notifications(int $userId, int $limit = 30): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id,type,data,is_read,created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['data'] ?? '{}'), true);
            $row['data'] = is_array($decoded) ? $decoded : [];
            $row['is_read'] = (int) ($row['is_read'] ?? 0);
        }
        return $rows;
    }

    public function unreadNotificationCount(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        return (int) (($stmt->fetch()['c'] ?? 0));
    }

    public function markNotificationsRead(int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
    }
}
