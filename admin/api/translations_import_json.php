<?php
require_once __DIR__ . '/_init.php';

$payload = trim((string) ($_POST['json_payload'] ?? ''));
$data = json_decode($payload, true);
if (!is_array($data)) {
    json_response(false, 'JSON formatı geçersiz');
}

$stmt = db()->prepare('INSERT INTO translations(lang_code,group_name,key_name,text_value) VALUES(:l,:g,:k,:v) ON DUPLICATE KEY UPDATE text_value=VALUES(text_value)');
$count = 0;
foreach ($data as $lang => $groups) {
    if (!is_array($groups)) {
        continue;
    }
    foreach ($groups as $group => $items) {
        if (!is_array($items)) {
            continue;
        }
        foreach ($items as $key => $value) {
            $stmt->execute(['l' => (string) $lang, 'g' => (string) $group, 'k' => (string) $key, 'v' => (string) $value]);
            $count++;
        }
    }
}
json_response(true, "{$count} çeviri içeri aktarıldı");
