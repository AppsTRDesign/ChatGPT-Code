<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/env.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$data = json_decode(file_get_contents(__DIR__ . '/data/world_data.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = Database::connection();
$pdo->beginTransaction();

$pdo->exec('DELETE FROM regions');
$pdo->exec('DELETE FROM countries');

$countryStmt = $pdo->prepare('INSERT INTO countries(id,name,slug,iso_code,flag_url,color,government_type,created_at) VALUES(:id,:name,:slug,:iso_code,:flag_url,:color,:government_type,NOW())');
foreach ($data['countries'] as $country) {
    $countryStmt->execute([
        'id' => $country['id'],
        'name' => $country['name'],
        'slug' => $country['slug'],
        'iso_code' => strtoupper((string) $country['slug']),
        'flag_url' => $country['flag_url'],
        'color' => $country['color'],
        'government_type' => $country['government_type'],
    ]);
}

$map = [];
foreach ($data['countries'] as $country) {
    $map[$country['slug']] = $country['id'];
}

$regionStmt = $pdo->prepare('INSERT INTO regions(id,country_id,name,slug,lat,lng,polygon_json,population,resource_type,owner_country_id,created_at)
VALUES(:id,:country_id,:name,:slug,:lat,:lng,:polygon_json,:population,:resource_type,:owner_country_id,NOW())');
$capitalByCountry = [];

foreach ($data['regions'] as $region) {
    $countryId = $map[$region['country_slug']];
    $regionStmt->execute([
        'id' => $region['id'],
        'country_id' => $countryId,
        'name' => $region['name'],
        'slug' => $region['slug'],
        'lat' => $region['lat'],
        'lng' => $region['lng'],
        'polygon_json' => json_encode($region['polygon_json'], JSON_THROW_ON_ERROR),
        'population' => $region['population'],
        'resource_type' => $region['resource_type'],
        'owner_country_id' => $countryId,
    ]);

    $capitalByCountry[$countryId] ??= (int) $region['id'];
}

$capitalStmt = $pdo->prepare('UPDATE countries SET capital_region_id = :capital_region_id WHERE id = :country_id');
foreach ($capitalByCountry as $countryId => $regionId) {
    $capitalStmt->execute(['capital_region_id' => $regionId, 'country_id' => $countryId]);
}

$pdo->commit();
echo "Seeded " . count($data['countries']) . " countries and " . count($data['regions']) . " regions\n";
