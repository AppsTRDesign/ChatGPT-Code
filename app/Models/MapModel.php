<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MapModel
{
    public function countries(): array
    {
        $sql = 'SELECT id,name,slug,flag_url,color,government_type,capital_region_id,created_at FROM countries ORDER BY name';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function regions(): array
    {
        $sql = 'SELECT r.id,r.country_id,r.name,r.slug,r.lat,r.lng,r.polygon_json,
                (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population,
                r.resource_type,r.owner_country_id,r.army_level,r.education_level,r.hospital_level,r.airport_level,r.port_level,r.is_coastal,
                c.name AS country_name,c.color AS country_color,oc.name AS owner_country_name
                FROM regions r
                JOIN countries c ON c.id = r.country_id
                LEFT JOIN countries oc ON oc.id = r.owner_country_id
                ORDER BY r.id';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function regionById(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM regions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $regionId]);
        return $stmt->fetch() ?: null;
    }

    public function dashboardStats(): array
    {
        $topRegions = Database::connection()->query(
            'SELECT r.id,r.name,c.name AS country_name,
             (r.army_level + r.education_level + r.hospital_level + r.airport_level + IF(r.is_coastal=1,r.port_level,0)) AS score,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population
             FROM regions r JOIN countries c ON c.id=r.country_id
             ORDER BY score DESC LIMIT 10'
        )->fetchAll();

        $topCountries = Database::connection()->query(
            'SELECT c.id,c.name,
             AVG(r.army_level + r.education_level + r.hospital_level + r.airport_level + IF(r.is_coastal=1,r.port_level,0)) AS avg_score,
             SUM((SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id)) AS population
             FROM countries c JOIN regions r ON r.country_id = c.id
             GROUP BY c.id,c.name
             ORDER BY avg_score DESC LIMIT 10'
        )->fetchAll();

        return ['top_regions' => $topRegions, 'top_countries' => $topCountries];
    }
}
