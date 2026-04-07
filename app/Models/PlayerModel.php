<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PlayerModel
{
    public function createProfile(int $userId, int $regionId, int $countryId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO player_profiles(user_id,current_region_id,current_country_id,energy,created_at) VALUES(:user_id,:current_region_id,:current_country_id,100,NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'current_region_id' => $regionId,
            'current_country_id' => $countryId,
        ]);
    }

    public function createCitizenship(int $userId, int $countryId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO citizenships(user_id,country_id,is_homeland,created_at) VALUES(:user_id,:country_id,1,NOW())'
        );
        $stmt->execute(['user_id' => $userId, 'country_id' => $countryId]);
    }

    public function me(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id,u.username,u.email,p.energy,p.current_region_id,p.current_country_id,
                    r.name AS current_region_name,r.lat AS current_region_lat,r.lng AS current_region_lng,c.name AS current_country_name,c.color AS current_country_color,
                    ch.country_id AS citizenship_country_id,cc.name AS citizenship_country_name
             FROM users u
             JOIN player_profiles p ON p.user_id = u.id
             JOIN regions r ON r.id = p.current_region_id
             JOIN countries c ON c.id = p.current_country_id
             LEFT JOIN citizenships ch ON ch.user_id = u.id AND ch.is_homeland = 1
             LEFT JOIN countries cc ON cc.id = ch.country_id
             WHERE u.id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function updateLocation(int $userId, int $regionId, int $countryId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE player_profiles SET current_region_id = :region_id, current_country_id = :country_id WHERE user_id = :user_id'
        );
        $stmt->execute(['region_id' => $regionId, 'country_id' => $countryId, 'user_id' => $userId]);
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
}
