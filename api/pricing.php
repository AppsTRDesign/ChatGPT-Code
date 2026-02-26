<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

if (!verify_csrf($_POST['csrf'] ?? null)) {
    json_response(false, 'CSRF mismatch');
}

$lang = current_lang();
$countryId = (int) ($_POST['country_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$stmt = db()->prepare('SELECT pc.price_amount, COALESCE(ctry_t.name, c.name) country_name, COALESCE(cat_t.title, cat.title) category_title, COALESCE(cat_t.description, cat.description) description FROM price_configs pc JOIN countries c ON c.id = pc.country_id JOIN price_categories cat ON cat.id = pc.category_id LEFT JOIN country_translations ctry_t ON ctry_t.country_id = c.id AND ctry_t.lang_code = :lang LEFT JOIN price_category_translations cat_t ON cat_t.category_id = cat.id AND cat_t.lang_code = :lang2 WHERE pc.country_id = :country AND pc.category_id = :category LIMIT 1');
$stmt->execute(['country' => $countryId, 'category' => $categoryId, 'lang' => $lang, 'lang2' => $lang]);
$row = $stmt->fetch();

if (!$row) {
    json_response(false, t('front', 'pricing_not_found'));
}

json_response(true, t('front', 'pricing_found'), $row);
