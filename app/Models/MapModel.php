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
                r.resource_type,r.owner_country_id,r.army_level,r.education_level,r.hospital_level,r.airport_level,r.port_level,r.is_coastal,r.has_sea_access,r.region_type,r.parent_country_region_id,r.neighbors_json,
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
             WHERE r.region_type = "region"
             ORDER BY score DESC LIMIT 10'
        )->fetchAll();

        $topCountries = Database::connection()->query(
            'SELECT r.id,c.id AS country_id,c.name,r.name AS capital_name,
             (r.army_level + r.education_level + r.hospital_level + r.airport_level + IF(r.is_coastal=1,r.port_level,0)) AS avg_score,
             (SELECT COUNT(*) FROM regions rr WHERE rr.parent_country_region_id = r.id OR rr.id = r.id) AS region_count,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population
             FROM regions r JOIN countries c ON c.id = r.country_id
             WHERE r.region_type = "country"
             ORDER BY avg_score DESC LIMIT 10'
        )->fetchAll();

        return ['top_regions' => $topRegions, 'top_countries' => $topCountries];
    }

    public function regionDetail(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,c.name AS country_name,c.flag_url AS country_flag,c.color AS country_color,
                    oc.name AS owner_country_name,
                    (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id=r.id) AS population
             FROM regions r
             JOIN countries c ON c.id = r.country_id
             LEFT JOIN countries oc ON oc.id = r.owner_country_id
             WHERE r.id=:id LIMIT 1'
        );
        $stmt->execute(['id' => $regionId]);
        $row = $stmt->fetch() ?: null;
        if (!$row) {
            return null;
        }
        $neighbors = json_decode((string) ($row['neighbors_json'] ?? '[]'), true);
        $neighbors = is_array($neighbors) ? array_values(array_filter(array_map('intval', $neighbors))) : [];
        if (!$neighbors) {
            $row['neighbors'] = [];
            return $row;
        }
        $in = implode(',', array_fill(0, count($neighbors), '?'));
        $q = Database::connection()->prepare("SELECT id,name,country_id FROM regions WHERE id IN ($in)");
        $q->execute($neighbors);
        $row['neighbors'] = $q->fetchAll();
        return $row;
    }

    public function countryDetailFromRegion(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,c.name AS country_name,c.flag_url,c.color,c.government_type
             FROM regions r
             JOIN countries c ON c.id = r.country_id
             WHERE r.id=:id AND r.region_type="country" LIMIT 1'
        );
        $stmt->execute(['id' => $regionId]);
        $countryRegion = $stmt->fetch() ?: null;
        if (!$countryRegion) {
            return null;
        }

        $regionsStmt = Database::connection()->prepare(
            'SELECT id,name,army_level,education_level,hospital_level,airport_level,port_level,region_type
             FROM regions
             WHERE id=:id OR parent_country_region_id=:id
             ORDER BY id'
        );
        $regionsStmt->execute(['id' => $regionId]);
        $countryRegion['regions'] = $regionsStmt->fetchAll();
        $countryRegion['region_count'] = count($countryRegion['regions']);
        return $countryRegion;
    }
}
