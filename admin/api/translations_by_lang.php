<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$lang = trim((string) ($_GET['lang'] ?? 'en'));
$stmt = db()->prepare('SELECT group_name, key_name, text_value FROM translations WHERE lang_code = :l ORDER BY group_name, key_name');
$stmt->execute(['l' => $lang]);
$rows = $stmt->fetchAll();
$out = [];
foreach ($rows as $r) {
    $out[$r['group_name']][$r['key_name']] = $r['text_value'];
}
json_response(true, 'ok', $out);
