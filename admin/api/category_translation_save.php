<?php
require_once __DIR__ . '/_init.php';

$stmt = db()->prepare('INSERT INTO price_category_translations(category_id,lang_code,title,description) VALUES(:id,:lang,:title,:description) ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description)');
$stmt->execute([
    'id' => (int) ($_POST['category_id'] ?? 0),
    'lang' => trim($_POST['lang_code'] ?? DEFAULT_LANG),
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
]);

json_response(true, 'Kategori çevirisi kaydedildi');
