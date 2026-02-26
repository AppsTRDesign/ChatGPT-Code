<?php
require_once __DIR__ . '/_init.php';

$keys = ['site_name','company_name','company_email','company_phone','company_address','meta_title','meta_description','logo_path','favicon_path','company_latitude','company_longitude','yandex_api_key'];
$stmt = db()->prepare('INSERT INTO settings(key_name, value) VALUES(:k,:v) ON DUPLICATE KEY UPDATE value=VALUES(value)');

$uploadDir = __DIR__ . '/../../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

if (!empty($_FILES['logo_file']['tmp_name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
    $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION) ?: 'png';
    $name = 'logo_' . time() . '.' . strtolower($ext);
    move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . '/' . $name);
    $_POST['logo_path'] = '/uploads/' . $name;
}

if (!empty($_FILES['favicon_file']['tmp_name']) && is_uploaded_file($_FILES['favicon_file']['tmp_name'])) {
    $ext = pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION) ?: 'ico';
    $name = 'favicon_' . time() . '.' . strtolower($ext);
    move_uploaded_file($_FILES['favicon_file']['tmp_name'], $uploadDir . '/' . $name);
    $_POST['favicon_path'] = '/uploads/' . $name;
}

foreach ($keys as $k) {
    $stmt->execute(['k' => $k, 'v' => trim((string) ($_POST[$k] ?? ''))]);
}

json_response(true, 'Ayarlar kaydedildi');
