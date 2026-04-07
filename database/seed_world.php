<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/env.php';
require_once __DIR__ . '/../app/Helpers/config.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$data = json_decode((string) file_get_contents(__DIR__ . '/data/world_data.json'), true);
if (!is_array($data)) {
    throw new RuntimeException('Invalid world_data.json');
}

$pdo = Database::connection();

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('DELETE FROM citizenships');
$pdo->exec('DELETE FROM player_travel');
$pdo->exec('DELETE FROM travel_logs');
$pdo->exec('DELETE FROM player_profiles');
$pdo->exec('DELETE FROM regions');
$pdo->exec('DELETE FROM countries');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$countryStmt = $pdo->prepare('INSERT INTO countries(id,name,slug,iso_code,flag_url,created_at) VALUES(:id,:name,:slug,:iso_code,:flag_url,NOW())');
$countryBySlug = [];
foreach ($data['countries'] as $country) {
    $countryStmt->execute([
        'id' => (int) $country['id'],
        'name' => $country['name'],
        'slug' => strtolower((string) $country['slug']),
        'iso_code' => strtoupper((string) $country['slug']),
        'flag_url' => $country['flag_url'],
    ]);
    $countryBySlug[strtolower((string) $country['slug'])] = $country;
}

$palette = ['#2563eb','#16a34a','#dc2626','#ea580c','#7c3aed','#0891b2','#ca8a04','#9333ea','#be123c','#0f766e'];
$regionStmt = $pdo->prepare(
    'INSERT INTO regions(id,country_id,country_code,country_name,name,slug,lat,lng,polygon_json,population,resource_type,owner_region_id,region_type,parent_country_region_id,capital_region_id,government_type,color,flag_url,neighbors_json,army_level,education_level,hospital_level,airport_level,port_level,is_coastal,has_sea_access,created_at)
     VALUES(:id,:country_id,:country_code,:country_name,:name,:slug,:lat,:lng,:polygon_json,:population,:resource_type,NULL,:region_type,:parent_country_region_id,:capital_region_id,:government_type,:color,:flag_url,JSON_ARRAY(),1,1,1,1,0,0,0,NOW())'
);

$countryCapitalByCountryId = [];
foreach ($data['regions'] as $idx => $region) {
    $slug = strtolower((string) $region['country_slug']);
    $country = $countryBySlug[$slug] ?? null;
    if (!$country) {
        continue;
    }

    $countryId = (int) $country['id'];
    if (!isset($countryCapitalByCountryId[$countryId])) {
        $countryCapitalByCountryId[$countryId] = (int) $region['id'];
    }

    $capitalId = $countryCapitalByCountryId[$countryId];
    $isCapitalRegion = (int) $region['id'] === $capitalId;

    $regionStmt->execute([
        'id' => (int) $region['id'],
        'country_id' => $countryId,
        'country_code' => strtoupper($slug),
        'country_name' => $country['name'],
        'name' => $region['name'],
        'slug' => $region['slug'],
        'lat' => (float) $region['lat'],
        'lng' => (float) $region['lng'],
        'polygon_json' => json_encode($region['polygon_json'], JSON_UNESCAPED_UNICODE),
        'population' => (int) ($region['population'] ?? 0),
        'resource_type' => $region['resource_type'] ?? 'agriculture',
        'region_type' => $isCapitalRegion ? 'country' : 'region',
        'parent_country_region_id' => $isCapitalRegion ? null : $capitalId,
        'capital_region_id' => $capitalId,
        'government_type' => (($country['government_type'] ?? 'republic') === 'dictatorship') ? 'dictatorship' : 'republic',
        'color' => $palette[$idx % count($palette)],
        'flag_url' => $country['flag_url'],
    ]);
}

$pdo->exec('UPDATE regions SET owner_region_id = COALESCE(parent_country_region_id, id)');
$pdo->exec('UPDATE regions SET capital_region_id = id WHERE capital_region_id IS NULL');
echo "Seeded " . count($data['countries']) . " countries and " . count($data['regions']) . " regions\n";
