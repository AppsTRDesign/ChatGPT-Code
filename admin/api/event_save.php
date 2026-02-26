<?php
require_once __DIR__ . '/_init.php';

$tracking = trim((string) $_POST['tracking_number']);
$s = db()->prepare('SELECT id FROM shipments WHERE tracking_number=:t LIMIT 1');
$s->execute(['t'=>$tracking]);
$sid=(int)$s->fetchColumn();
if(!$sid){json_response(false,'Kargo yok');}

$lat = (float) ($_POST['latitude'] ?? 0);
$lng = (float) ($_POST['longitude'] ?? 0);

db()->prepare('INSERT INTO shipment_events(shipment_id,status_code,status_note,country,city,latitude,longitude,created_at) VALUES(:sid,:code,:note,:country,:city,:lat,:lng,NOW())')->execute(['sid'=>$sid,'code'=>trim((string)$_POST['status_code']),'note'=>trim((string)($_POST['status_note']??'')),'country'=>trim((string)($_POST['country']??'')),'city'=>trim((string)($_POST['city']??'')),'lat'=>$lat,'lng'=>$lng]);
db()->prepare('UPDATE shipments SET current_status=:st,current_latitude=:lat,current_longitude=:lng,updated_at=NOW() WHERE id=:id')->execute(['st'=>trim((string)$_POST['status_code']),'lat'=>$lat,'lng'=>$lng,'id'=>$sid]);
json_response(true,'Durum eklendi');
