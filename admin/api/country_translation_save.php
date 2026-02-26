<?php
require_once __DIR__ . '/_init.php';

$stmt = db()->prepare('INSERT INTO country_translations(country_id,lang_code,name) VALUES(:id,:lang,:name) ON DUPLICATE KEY UPDATE name=VALUES(name)');
$stmt->execute([
    'id' => (int) ($_POST['country_id'] ?? 0),
    'lang' => trim($_POST['lang_code'] ?? DEFAULT_LANG),
    'name' => trim($_POST['name'] ?? ''),
]);

json_response(true, 'Ülke çevirisi kaydedildi');
