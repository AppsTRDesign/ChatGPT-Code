<?php
require_once __DIR__ . '/_init.php';

$code = strtolower(trim($_POST['code'] ?? ''));
if ($code === '') {
    json_response(false, 'Dil kodu gerekli');
}

$stmt = db()->prepare('INSERT INTO languages(code,name,is_active,sort_order) VALUES(:code,:name,1,:sort) ON DUPLICATE KEY UPDATE name=VALUES(name), is_active=1, sort_order=VALUES(sort_order)');
$stmt->execute([
    'code' => $code,
    'name' => trim($_POST['name'] ?? strtoupper($code)),
    'sort' => (int) ($_POST['sort_order'] ?? 10),
]);

json_response(true, 'Dil kaydedildi');
