<?php
require_once __DIR__ . '/_init.php';

$stmt = db()->prepare('INSERT INTO shipments(tracking_number,origin_country,origin_city,destination_country,destination_city,current_status,description,sender_name,sender_company,sender_phone,receiver_name,receiver_phone,receiver_address,current_latitude,current_longitude,created_at,updated_at) VALUES(:tracking,:oc,:oci,:dc,:dci,:status,:desc,:sn,:sc,:sp,:rn,:rp,:ra,:lat,:lng,NOW(),NOW())');
$stmt->execute([
'tracking'=>trim((string)$_POST['tracking_number']),'oc'=>trim((string)$_POST['origin_country']),'oci'=>trim((string)$_POST['origin_city']),'dc'=>trim((string)$_POST['destination_country']),'dci'=>trim((string)$_POST['destination_city']),'status'=>trim((string)$_POST['current_status']),'desc'=>trim((string)($_POST['description']??'')),'sn'=>trim((string)($_POST['sender_name']??'')),'sc'=>trim((string)($_POST['sender_company']??'')),'sp'=>trim((string)($_POST['sender_phone']??'')),'rn'=>trim((string)($_POST['receiver_name']??'')),'rp'=>trim((string)($_POST['receiver_phone']??'')),'ra'=>trim((string)($_POST['receiver_address']??'')),'lat'=>(float)($_POST['current_latitude']??0),'lng'=>(float)($_POST['current_longitude']??0)]);
json_response(true,'Kargo kaydedildi');
