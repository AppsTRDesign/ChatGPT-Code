<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$countryId = (int) ($_GET['country_id'] ?? 0);
$lang = current_lang();
$stmt = db()->prepare('SELECT cat.id, COALESCE(ct.title, cat.title) AS title FROM price_categories cat JOIN price_configs pc ON pc.category_id = cat.id LEFT JOIN price_category_translations ct ON ct.category_id = cat.id AND ct.lang_code = :lang WHERE pc.country_id = :cid GROUP BY cat.id, title ORDER BY title');
$stmt->execute(['cid' => $countryId, 'lang' => $lang]);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
