<?php
require_once __DIR__ . '/_init.php';

$id = (int) ($_POST['category_id'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$desc = trim((string) ($_POST['description'] ?? ''));
if ($title === '') {
    json_response(false, 'Kategori başlığı gerekli');
}

if ($id > 0) {
    db()->prepare('UPDATE price_categories SET title=:t, description=:d WHERE id=:id')->execute(['t' => $title, 'd' => $desc, 'id' => $id]);
    json_response(true, 'Kategori güncellendi');
}

db()->prepare('INSERT INTO price_categories(title,description) VALUES(:t,:d)')->execute(['t' => $title, 'd' => $desc]);
json_response(true, 'Kategori kaydedildi');
