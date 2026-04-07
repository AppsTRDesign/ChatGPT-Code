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
$pdo->exec('DELETE FROM region_taxes');
$pdo->exec('DELETE FROM country_visuals');
$pdo->exec('DELETE FROM country_economy');
$pdo->exec('DELETE FROM region_profile');
$pdo->exec('DELETE FROM region_infra');
$pdo->exec('DELETE FROM regions');
$pdo->exec('DELETE FROM countries');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$countryStmt = $pdo->prepare('INSERT INTO countries(id,name,slug,iso_code,flag_url,color,created_at) VALUES(:id,:name,:slug,:iso_code,:flag_url,:color,NOW())');
$countryBySlug = [];
foreach ($data['countries'] as $country) {
    $countryStmt->execute([
        'id' => (int) $country['id'],
        'name' => $country['name'],
        'slug' => strtolower((string) $country['slug']),
        'iso_code' => strtoupper((string) $country['slug']),
        'flag_url' => $country['flag_url'],
        'color' => $country['color'] ?? '#7c3aed',
    ]);
    $countryBySlug[strtolower((string) $country['slug'])] = $country;
}

$palette = ['#2563eb','#16a34a','#dc2626','#ea580c','#7c3aed','#0891b2','#ca8a04','#9333ea','#be123c','#0f766e'];
$regionStmt = $pdo->prepare('INSERT INTO regions(id,country_id,country_code,country_name,name,slug,lat,lng,polygon_json,population,owner_region_id,region_type,government_type,neighbors_json,created_at) VALUES(:id,:country_id,:country_code,:country_name,:name,:slug,:lat,:lng,:polygon_json,:population,NULL,:region_type,:government_type,JSON_ARRAY(),NOW())');

$capitalByCountryId = [];
foreach ($data['regions'] as $idx => $region) {
    $slug = strtolower((string) $region['country_slug']);
    $country = $countryBySlug[$slug] ?? null;
    if (!$country) continue;
    $countryId = (int) $country['id'];
    if (!isset($capitalByCountryId[$countryId])) {
        $capitalByCountryId[$countryId] = (int) $region['id'];
    }
    $capitalId = $capitalByCountryId[$countryId];
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
        'population' => 0,
        'region_type' => ((int) $region['id'] === $capitalId) ? 'country' : 'region',
        'government_type' => (($country['government_type'] ?? 'republic') === 'dictatorship') ? 'dictatorship' : 'republic',
    ]);
}
$pdo->exec('UPDATE regions r JOIN (SELECT country_id, MIN(id) capital_id FROM regions GROUP BY country_id) x ON x.country_id = r.country_id SET r.owner_region_id = x.capital_id');

$infraStmt = $pdo->prepare('INSERT INTO region_infra(region_id,airport_count,army_count,hospital_count,education_count,school_count,port_count,airport_level,army_level,hospital_level,education_level,school_level,port_level) VALUES(:region_id,100,100,100,100,100,100,1,1,1,1,1,1)');
$profileStmt = $pdo->prepare('INSERT INTO region_profile(region_id,capital_region_id,is_coastal) VALUES(:region_id,:capital_region_id,0)');
$taxStmt = $pdo->prepare('INSERT INTO region_taxes(region_id,factory_tax_uranium,factory_tax_mineral,factory_tax_oil,factory_tax_gold,factory_tax_diamond) VALUES(:region_id,0,0,0,0,0)');
$visualStmt = $pdo->prepare('INSERT INTO country_visuals(country_region_id,color,flag_url) VALUES(:country_region_id,:color,:flag_url)');
$econStmt = $pdo->prepare('INSERT INTO country_economy(country_region_id,resource_type,treasury_state_money,treasury_gold,treasury_uranium,treasury_mineral,treasury_oil,treasury_diamond,general_tax_rate,sales_tax_rate,factory_tax_uranium,factory_tax_mineral,factory_tax_oil,factory_tax_gold,factory_tax_diamond) VALUES(:country_region_id,:resource_type,250000000,250000000,1000000,1000000,1000000,1000000,10,0,0,0,0,0,0)');

$regions = $pdo->query('SELECT id,country_id,country_code,owner_region_id,region_type FROM regions')->fetchAll();
foreach ($regions as $r) {
    $infraStmt->execute(['region_id' => (int) $r['id']]);
    $profileStmt->execute(['region_id' => (int) $r['id'], 'capital_region_id' => (int) $r['owner_region_id']]);
    $taxStmt->execute(['region_id' => (int) $r['id']]);
    if ((string) $r['region_type'] === 'country') {
        $econStmt->execute(['country_region_id' => (int) $r['id'], 'resource_type' => 'mineral']);
        $slug = strtolower((string) ($r['country_code'] ?? ''));
        $country = $countryBySlug[$slug] ?? null;
        $visualStmt->execute(['country_region_id' => (int) $r['id'], 'color' => $palette[((int) $r['country_id']) % count($palette)], 'flag_url' => $country['flag_url'] ?? null]);
    }
}

echo "Seeded " . count($data['countries']) . " countries and " . count($data['regions']) . " regions\n";
