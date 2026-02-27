<?php
require_once __DIR__ . '/_init.php';

$tracking = trim((string) $_POST['tracking_number']);
$s = db()->prepare('SELECT id FROM shipments WHERE tracking_number=:t LIMIT 1');
$s->execute(['t'=>$tracking]);
$sid=(int)$s->fetchColumn();
if(!$sid){json_response(false,'Kargo yok');}

$lat = (float) ($_POST['latitude'] ?? 0);
$lng = (float) ($_POST['longitude'] ?? 0);
$countryId = (int)($_POST['country_id'] ?? 0);
$city = trim((string)($_POST['city'] ?? ''));
$countryName = '';
if ($countryId > 0) {
    $lang = current_lang();
    $cStmt = db()->prepare('SELECT COALESCE(ct.name, c.name) AS name FROM countries c LEFT JOIN country_translations ct ON ct.country_id = c.id AND ct.lang_code = :lang WHERE c.id = :id LIMIT 1');
    $cStmt->execute(['id'=>$countryId,'lang'=>$lang]);
    $countryName = (string)($cStmt->fetchColumn() ?: '');
}

$statusCode = trim((string)$_POST['status_code']);
db()->prepare('INSERT INTO shipment_events(shipment_id,status_code,status_note,country,city,country_id,latitude,longitude,created_at) VALUES(:sid,:code,:note,:country,:city,:country_id,:lat,:lng,NOW())')->execute([
    'sid'=>$sid,
    'code'=>$statusCode,
    'note'=>trim((string)($_POST['status_note']??'')),
    'country'=>$countryName,
    'city'=>$city,
    'country_id'=>$countryId > 0 ? $countryId : null,
    'lat'=>$lat,
    'lng'=>$lng
]);
db()->prepare('UPDATE shipments SET current_status=:st,current_latitude=:lat,current_longitude=:lng,updated_at=NOW() WHERE id=:id')->execute(['st'=>$statusCode,'lat'=>$lat,'lng'=>$lng,'id'=>$sid]);
json_response(true,'Durum eklendi');
