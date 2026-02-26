<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$lang = preg_replace('/[^a-z_\-]/i', '', (string) ($_GET['lang'] ?? current_lang()));
$lat = trim((string) ($_GET['lat'] ?? ''));
$lng = trim((string) ($_GET['lng'] ?? ''));
$address = trim((string) ($_GET['address'] ?? ''));

try {
    $key = settings()['yandex_api_key'] ?? 'd0b1a4c0-60eb-4a39-b34a-61c68fffc2d6';
} catch (Throwable $e) {
    $key = 'd0b1a4c0-60eb-4a39-b34a-61c68fffc2d6';
}

if ($address !== '') {
    $geocode = urlencode($address);
} elseif ($lat !== '' && $lng !== '') {
    $geocode = urlencode($lng . ',' . $lat);
} else {
    json_response(false, 'Parametre eksik');
}

$url = "https://geocode-maps.yandex.ru/1.x/?apikey={$key}&format=json&geocode={$geocode}&lang={$lang}";
$resp = @file_get_contents($url);
if ($resp === false) {
    json_response(false, 'Yandex geocode alınamadı');
}

header('Content-Type: application/json; charset=utf-8');
echo $resp;
