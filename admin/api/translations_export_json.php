<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$rows = db()->query('SELECT lang_code, group_name, key_name, text_value FROM translations ORDER BY lang_code, group_name, key_name')->fetchAll();
$out = [];
foreach ($rows as $row) {
    $out[$row['lang_code']][$row['group_name']][$row['key_name']] = $row['text_value'];
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="translations.json"');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
