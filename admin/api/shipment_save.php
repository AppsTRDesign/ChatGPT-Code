<?php
require_once __DIR__ . '/_init.php';

$params = [
    'tracking'=>trim((string)$_POST['tracking_number']),
    'ocid'=>(int)($_POST['origin_country_id'] ?? 0),
    'oci'=>trim((string)$_POST['origin_city']),
    'dcid'=>(int)($_POST['destination_country_id'] ?? 0),
    'dci'=>trim((string)$_POST['destination_city']),
    'status'=>trim((string)$_POST['current_status']),
    'desc'=>trim((string)($_POST['description']??'')),
    'sn'=>trim((string)($_POST['sender_name']??'')),
    'sc'=>trim((string)($_POST['sender_company']??'')),
    'sp'=>trim((string)($_POST['sender_phone']??'')),
    'rn'=>trim((string)($_POST['receiver_name']??'')),
    'rp'=>trim((string)($_POST['receiver_phone']??'')),
    'ra'=>trim((string)($_POST['receiver_address']??'')),
    'lat'=>(float)($_POST['current_latitude']??0),
    'lng'=>(float)($_POST['current_longitude']??0),
    'olat'=>(float)($_POST['origin_latitude']??0),
    'olng'=>(float)($_POST['origin_longitude']??0),
    'dlat'=>(float)($_POST['destination_latitude']??0),
    'dlng'=>(float)($_POST['destination_longitude']??0),
];

if ($params['ocid'] <= 0 || $params['dcid'] <= 0) {
    json_response(false, 'Çıkış ve varış ülkesi seçmelisiniz.');
}

db()->prepare('INSERT INTO shipments(tracking_number,origin_country_id,origin_city,destination_country_id,destination_city,current_status,description,sender_name,sender_company,sender_phone,receiver_name,receiver_phone,receiver_address,current_latitude,current_longitude,origin_latitude,origin_longitude,destination_latitude,destination_longitude,created_at,updated_at) VALUES(:tracking,:ocid,:oci,:dcid,:dci,:status,:desc,:sn,:sc,:sp,:rn,:rp,:ra,:lat,:lng,:olat,:olng,:dlat,:dlng,NOW(),NOW())')->execute($params);
json_response(true,'Kargo kaydedildi');
