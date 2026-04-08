<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MapModel
{
    public function countries(): array
    {
        $sql = 'SELECT r.id,r.country_id AS nation_country_id,r.name,r.country_name,r.country_code,r.government_type,cv.color,cv.flag_url,rp.capital_region_id
                FROM regions r
                LEFT JOIN region_profile rp ON rp.region_id = r.id
                LEFT JOIN country_visuals cv ON cv.country_region_id = r.id
                WHERE r.region_type = "country" ORDER BY r.country_name,r.name';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function regions(): array
    {
        $sql = 'SELECT r.id,r.country_id,r.country_code,r.country_name,r.name,r.slug,r.lat,r.lng,r.polygon_json,
                (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population,
                ce.resource_type,r.owner_region_id,
                ri.airport_count,ri.army_count,ri.hospital_count,ri.education_count,ri.school_count,ri.port_count,
                ri.airport_level,ri.army_level,ri.hospital_level,ri.education_level,ri.school_level,ri.port_level,
                rp.is_coastal,r.region_type,rp.capital_region_id,r.neighbors_json,cv.color,cv.flag_url,
                (ri.airport_count+ri.army_count+ri.hospital_count+ri.education_count+ri.school_count+ri.port_count) AS total_score,
                owner.name AS owner_region_name
                FROM regions r
                JOIN region_infra ri ON ri.region_id = r.id
                JOIN region_profile rp ON rp.region_id = r.id
                LEFT JOIN regions owner ON owner.id = r.owner_region_id
                LEFT JOIN country_economy ce ON ce.country_region_id = r.owner_region_id
                LEFT JOIN country_visuals cv ON cv.country_region_id = r.owner_region_id
                ORDER BY r.id';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function regionById(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,ri.airport_count,ri.army_count,ri.hospital_count,ri.education_count,ri.school_count,ri.port_count,
                    ri.airport_level,ri.army_level,ri.hospital_level,ri.education_level,ri.school_level,ri.port_level,
                    rp.capital_region_id,rp.is_coastal,ce.general_tax_rate,ce.resource_type
             FROM regions r
             JOIN region_infra ri ON ri.region_id=r.id
             JOIN region_profile rp ON rp.region_id=r.id
             LEFT JOIN country_economy ce ON ce.country_region_id = r.owner_region_id
                LEFT JOIN country_visuals cv ON cv.country_region_id = r.owner_region_id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $regionId]);
        return $stmt->fetch() ?: null;
    }

    public function dashboardStats(): array
    {
        $topRegions = Database::connection()->query(
            'SELECT r.id,r.name,r.country_name,
             (ri.airport_count+ri.army_count+ri.hospital_count+ri.education_count+ri.school_count+ri.port_count) AS score,
             ri.airport_level,ri.army_level,ri.hospital_level,ri.education_level,ri.school_level,ri.port_level,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id = r.id) AS population
             FROM regions r JOIN region_infra ri ON ri.region_id=r.id
             ORDER BY score DESC LIMIT 10'
        )->fetchAll();

        $topCountries = Database::connection()->query(
            'SELECT c.id,c.country_name,c.name AS capital_name,c.government_type,
             SUM(ri.airport_count+ri.army_count+ri.hospital_count+ri.education_count+ri.school_count+ri.port_count) AS total_score,
             SUM(ri.airport_level) AS airport_total,
             SUM(ri.army_level) AS army_total,
             SUM(ri.hospital_level) AS hospital_total,
             SUM(ri.education_level) AS education_total,
             SUM(ri.port_level) AS port_total,
             COUNT(r.id) AS region_count,
             (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_country_region_id = c.id) AS population
             FROM regions c
             JOIN regions r ON r.owner_region_id = c.id
             JOIN region_infra ri ON ri.region_id = r.id
             WHERE c.region_type = "country"
             GROUP BY c.id,c.country_name,c.name,c.government_type
             ORDER BY total_score DESC LIMIT 10'
        )->fetchAll();

        $topAirports = Database::connection()->query('SELECT r.id,r.name,r.country_name,ri.airport_level AS value FROM regions r JOIN region_infra ri ON ri.region_id=r.id ORDER BY ri.airport_level DESC LIMIT 10')->fetchAll();
        $topArmies = Database::connection()->query('SELECT r.id,r.name,r.country_name,ri.army_level AS value FROM regions r JOIN region_infra ri ON ri.region_id=r.id ORDER BY ri.army_level DESC LIMIT 10')->fetchAll();
        $topHospitals = Database::connection()->query('SELECT r.id,r.name,r.country_name,ri.hospital_level AS value FROM regions r JOIN region_infra ri ON ri.region_id=r.id ORDER BY ri.hospital_level DESC LIMIT 10')->fetchAll();
        $topEducations = Database::connection()->query('SELECT r.id,r.name,r.country_name,ri.education_level AS value FROM regions r JOIN region_infra ri ON ri.region_id=r.id ORDER BY ri.education_level DESC LIMIT 10')->fetchAll();
        $topPorts = Database::connection()->query('SELECT r.id,r.name,r.country_name,ri.port_level AS value FROM regions r JOIN region_infra ri ON ri.region_id=r.id ORDER BY ri.port_level DESC LIMIT 10')->fetchAll();
        $topCountryPopulation = Database::connection()->query('SELECT c.id,c.country_name,COUNT(pp.user_id) AS total_population FROM regions c LEFT JOIN player_profiles pp ON pp.current_country_region_id = c.id WHERE c.region_type="country" GROUP BY c.id,c.country_name ORDER BY total_population DESC LIMIT 10')->fetchAll();
        $topRegionPopulation = Database::connection()->query('SELECT r.id,r.name,r.country_name,COUNT(pp.user_id) AS total_population FROM regions r LEFT JOIN player_profiles pp ON pp.current_region_id = r.id GROUP BY r.id,r.name,r.country_name ORDER BY total_population DESC LIMIT 10')->fetchAll();
        $topPlayers = Database::connection()->query(
            'SELECT u.id,u.username,p.level,p.xp,p.current_region_id,r.name AS region_name,c.name AS nation_name,c.flag_url AS nation_flag_url
             FROM player_profiles p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN regions r ON r.id = p.current_region_id
             LEFT JOIN countries c ON c.id = p.nation_country_id
             ORDER BY p.level DESC, p.xp DESC, u.id ASC
             LIMIT 10'
        )->fetchAll();

        return ['top_regions'=>$topRegions,'top_countries'=>$topCountries,'top_airports'=>$topAirports,'top_armies'=>$topArmies,'top_hospitals'=>$topHospitals,'top_educations'=>$topEducations,'top_ports'=>$topPorts,'top_country_population'=>$topCountryPopulation,'top_region_population'=>$topRegionPopulation,'top_players'=>$topPlayers];
    }

    public function regionDetail(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, owner.name AS owner_region_name,
                    ri.airport_count,ri.army_count,ri.hospital_count,ri.education_count,ri.school_count,ri.port_count,
                    ri.airport_level,ri.army_level,ri.hospital_level,ri.education_level,ri.school_level,ri.port_level,
                    rp.capital_region_id,rp.is_coastal,ce.resource_type,cv.color,cv.flag_url,
                    (ri.airport_count+ri.army_count+ri.hospital_count+ri.education_count+ri.school_count+ri.port_count) AS total_score,
                    (SELECT COUNT(*) FROM player_profiles pp WHERE pp.current_region_id=r.id) AS population
             FROM regions r
             JOIN region_infra ri ON ri.region_id=r.id
             JOIN region_profile rp ON rp.region_id=r.id
             LEFT JOIN country_economy ce ON ce.country_region_id = r.owner_region_id
                LEFT JOIN country_visuals cv ON cv.country_region_id = r.owner_region_id
             LEFT JOIN regions owner ON owner.id = r.owner_region_id
             WHERE r.id=:id LIMIT 1'
        );
        $stmt->execute(['id' => $regionId]);
        $row = $stmt->fetch() ?: null;
        if (!$row) return null;
        $neighbors = json_decode((string) ($row['neighbors_json'] ?? '[]'), true);
        $neighbors = is_array($neighbors) ? array_values(array_filter(array_map('intval', $neighbors))) : [];
        if (!$neighbors) { $row['neighbors'] = []; return $row; }
        $in = implode(',', array_fill(0, count($neighbors), '?'));
        $q = Database::connection()->prepare("SELECT id,name,country_name FROM regions WHERE id IN ($in)");
        $q->execute($neighbors);
        $row['neighbors'] = $q->fetchAll();
        return $row;
    }

    public function countryDetailFromRegion(int $regionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,rp.capital_region_id,ce.*,cv.color,cv.flag_url
             FROM regions r
             LEFT JOIN region_profile rp ON rp.region_id=r.id
             LEFT JOIN country_economy ce ON ce.country_region_id=r.id
             LEFT JOIN country_visuals cv ON cv.country_region_id=r.id
             WHERE r.id=:id AND r.region_type="country" LIMIT 1'
        );
        $stmt->execute(['id' => $regionId]);
        $countryRegion = $stmt->fetch() ?: null;
        if (!$countryRegion) return null;

        $regionsStmt = Database::connection()->prepare(
            'SELECT r.id,r.name,r.country_name,r.region_type,
                    ri.airport_count,ri.army_count,ri.hospital_count,ri.education_count,ri.school_count,ri.port_count,
                    ri.airport_level,ri.army_level,ri.hospital_level,ri.education_level,ri.school_level,ri.port_level
             FROM regions r JOIN region_infra ri ON ri.region_id=r.id
             WHERE r.owner_region_id=:id ORDER BY r.id'
        );
        $regionsStmt->execute(['id' => $regionId]);
        $countryRegion['regions'] = $regionsStmt->fetchAll();
        $countryRegion['region_count'] = count($countryRegion['regions']);
        return $countryRegion;
    }
}
