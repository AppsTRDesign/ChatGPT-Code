<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MapModel
{
    public function countries(): array
    {
        $sql = 'SELECT id,name,country_name,country_code,government_type,color,capital_region_id,flag_url FROM regions WHERE region_type = "country" ORDER BY country_name,name';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function regions(): array
    {
        $sql = 'SELECT r.id,r.country_id,r.country_code,r.country_name,r.name,r.slug,r.lat,r.lng,r.polygon_json,
                (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population,
                r.resource_type,r.owner_region_id,r.army_level,r.education_level,r.hospital_level,r.airport_level,r.port_level,r.is_coastal,r.has_sea_access,r.region_type,r.parent_country_region_id,r.capital_region_id,r.neighbors_json,r.color,r.flag_url,
                owner.name AS owner_region_name
                FROM regions r
                LEFT JOIN regions owner ON owner.id = r.owner_region_id
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
            'SELECT r.id,r.name,r.country_name,
             (r.army_level + r.education_level + r.hospital_level + r.airport_level + IF(r.is_coastal=1,r.port_level,0)) AS score,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population
             FROM regions r
             WHERE r.region_type = "region"
             ORDER BY score DESC LIMIT 10'
        )->fetchAll();

        $topCountries = Database::connection()->query(
            'SELECT r.id,r.country_name,r.name AS capital_name,r.government_type,
             (r.army_level + r.education_level + r.hospital_level + r.airport_level + IF(r.is_coastal=1,r.port_level,0)) AS avg_score,
             (SELECT COUNT(*) FROM regions rr WHERE rr.parent_country_region_id = r.id OR rr.id = r.id) AS region_count,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_country_region_id = r.id) AS population
             FROM regions r
             WHERE r.region_type = "country"
             ORDER BY avg_score DESC LIMIT 10'
        )->fetchAll();

        $topIndependents = Database::connection()->query(
            'SELECT r.id,r.name,r.country_name,
             (r.army_level + r.education_level + r.hospital_level + r.airport_level + IF(r.has_sea_access=1,r.port_level,0)) AS score,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population
             FROM regions r
             WHERE r.region_type = "independent"
             ORDER BY score DESC LIMIT 10'
        )->fetchAll();

        return ['top_regions' => $topRegions, 'top_countries' => $topCountries, 'top_independents' => $topIndependents];
    }

    public function regionDetail(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, owner.name AS owner_region_name,
                    (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id=r.id) AS population
             FROM regions r
             LEFT JOIN regions owner ON owner.id = r.owner_region_id
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
        $q = Database::connection()->prepare("SELECT id,name,country_name FROM regions WHERE id IN ($in)");
        $q->execute($neighbors);
        $row['neighbors'] = $q->fetchAll();
        return $row;
    }

    public function countryDetailFromRegion(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*
             FROM regions r
             WHERE r.id=:id AND r.region_type="country" LIMIT 1'
        );
        $stmt->execute(['id' => $regionId]);
        $countryRegion = $stmt->fetch() ?: null;
        if (!$countryRegion) {
            return null;
        }

        $regionsStmt = Database::connection()->prepare(
            'SELECT id,name,country_name,army_level,education_level,hospital_level,airport_level,port_level,region_type
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
