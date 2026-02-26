<?php require_once __DIR__ . '/_init.php';
$stmt=db()->prepare('INSERT INTO shipments(tracking_number,origin_country,origin_city,destination_country,destination_city,current_status,description,sender_name,sender_company,sender_phone,receiver_name,receiver_phone,receiver_address,created_at,updated_at) VALUES(:tracking,:oc,:oci,:dc,:dci,:status,:desc,:sn,:sc,:sp,:rn,:rp,:ra,NOW(),NOW())');
$stmt->execute([
'tracking'=>trim($_POST['tracking_number']),'oc'=>trim($_POST['origin_country']),'oci'=>trim($_POST['origin_city']),'dc'=>trim($_POST['destination_country']),'dci'=>trim($_POST['destination_city']),'status'=>trim($_POST['current_status']),'desc'=>trim($_POST['description']??''),'sn'=>trim($_POST['sender_name']??''),'sc'=>trim($_POST['sender_company']??''),'sp'=>trim($_POST['sender_phone']??''),'rn'=>trim($_POST['receiver_name']??''),'rp'=>trim($_POST['receiver_phone']??''),'ra'=>trim($_POST['receiver_address']??'')]);
json_response(true,'Kargo kaydedildi');
