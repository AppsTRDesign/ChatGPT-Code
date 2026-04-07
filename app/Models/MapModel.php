<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MapModel
{
    public function countries(): array
    {
        $sql = 'SELECT id,name,country_name,country_code,government_type,color,capital_region_id,flag_url
                FROM regions WHERE region_type = "country" ORDER BY country_name,name';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function regions(): array
    {
        $sql = 'SELECT r.id,r.country_id,r.country_code,r.country_name,r.name,r.slug,r.lat,r.lng,r.polygon_json,
                (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population,
                r.resource_type,r.owner_region_id,r.airport_building_count,r.army_building_count,r.hospital_building_count,r.education_building_count,r.port_count,r.is_coastal,r.region_type,r.capital_region_id,r.neighbors_json,r.color,r.flag_url,
                (r.airport_building_count+r.army_building_count+r.hospital_building_count+r.education_building_count+r.port_count) AS total_building_count,
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
             (r.airport_building_count+r.army_building_count+r.hospital_building_count+r.education_building_count+r.port_count) AS score,
             r.airport_building_count,r.army_building_count,r.hospital_building_count,r.education_building_count,r.port_count,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population
             FROM regions r
             ORDER BY score DESC LIMIT 10'
        )->fetchAll();

        $topCountries = Database::connection()->query(
            'SELECT c.id,c.country_name,c.name AS capital_name,c.government_type,
             SUM(r.airport_building_count+r.army_building_count+r.hospital_building_count+r.education_building_count+r.port_count) AS total_score,
             SUM(r.airport_building_count) AS airport_total,
             SUM(r.army_building_count) AS army_total,
             SUM(r.hospital_building_count) AS hospital_total,
             SUM(r.education_building_count) AS education_total,
             SUM(r.port_count) AS port_total,
             COUNT(r.id) AS region_count,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_country_region_id = c.id) AS population
             FROM regions c
             JOIN regions r ON r.owner_region_id = c.id
             WHERE c.region_type = "country"
             GROUP BY c.id,c.country_name,c.name,c.government_type
             ORDER BY total_score DESC LIMIT 10'
        )->fetchAll();

        $topAirports = Database::connection()->query('SELECT id,name,country_name,airport_building_count AS value FROM regions ORDER BY airport_building_count DESC LIMIT 10')->fetchAll();
        $topArmies = Database::connection()->query('SELECT id,name,country_name,army_building_count AS value FROM regions ORDER BY army_building_count DESC LIMIT 10')->fetchAll();
        $topHospitals = Database::connection()->query('SELECT id,name,country_name,hospital_building_count AS value FROM regions ORDER BY hospital_building_count DESC LIMIT 10')->fetchAll();
        $topEducations = Database::connection()->query('SELECT id,name,country_name,education_building_count AS value FROM regions ORDER BY education_building_count DESC LIMIT 10')->fetchAll();
        $topPorts = Database::connection()->query('SELECT id,name,country_name,port_count AS value FROM regions ORDER BY port_count DESC LIMIT 10')->fetchAll();

        return [
            'top_regions' => $topRegions,
            'top_countries' => $topCountries,
            'top_airports' => $topAirports,
            'top_armies' => $topArmies,
            'top_hospitals' => $topHospitals,
            'top_educations' => $topEducations,
            'top_ports' => $topPorts,
        ];
    }

    public function regionDetail(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, owner.name AS owner_region_name,
                    (r.airport_building_count+r.army_building_count+r.hospital_building_count+r.education_building_count+r.port_count) AS total_building_count,
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
        $stmt = Database::connection()->prepare('SELECT r.* FROM regions r WHERE r.id=:id AND r.region_type="country" LIMIT 1');
        $stmt->execute(['id' => $regionId]);
        $countryRegion = $stmt->fetch() ?: null;
        if (!$countryRegion) {
            return null;
        }

        $regionsStmt = Database::connection()->prepare(
            'SELECT id,name,country_name,airport_building_count,army_building_count,hospital_building_count,education_building_count,port_count,region_type
             FROM regions
             WHERE owner_region_id=:id
             ORDER BY id'
        );
        $regionsStmt->execute(['id' => $regionId]);
        $countryRegion['regions'] = $regionsStmt->fetchAll();
        $countryRegion['region_count'] = count($countryRegion['regions']);
        return $countryRegion;
    }
}
