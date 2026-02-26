<?php require_once __DIR__ . '/_init.php';
$tracking = trim($_POST['tracking_number']);
$s = db()->prepare('SELECT id FROM shipments WHERE tracking_number=:t LIMIT 1');$s->execute(['t'=>$tracking]);$sid=(int)$s->fetchColumn();
if(!$sid){json_response(false,'Kargo yok');}
db()->prepare('INSERT INTO shipment_events(shipment_id,status_code,status_note,country,city,latitude,longitude,created_at) VALUES(:sid,:code,:note,:country,:city,:lat,:lng,NOW())')->execute(['sid'=>$sid,'code'=>trim($_POST['status_code']),'note'=>trim($_POST['status_note']??''),'country'=>trim($_POST['country']??''),'city'=>trim($_POST['city']??''),'lat'=>trim($_POST['latitude']??''),'lng'=>trim($_POST['longitude']??'')]);
db()->prepare('UPDATE shipments SET current_status=:st,updated_at=NOW() WHERE id=:id')->execute(['st'=>trim($_POST['status_code']),'id'=>$sid]);
json_response(true,'Durum eklendi');
