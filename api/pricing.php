<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request');
}

if (!verify_csrf($_POST['csrf'] ?? null)) {
    json_response(false, 'CSRF mismatch');
}

$countryId = (int) ($_POST['country_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$stmt = db()->prepare('SELECT pc.price_amount, c.name country_name, cat.title category_title, cat.description FROM price_configs pc JOIN countries c ON c.id = pc.country_id JOIN price_categories cat ON cat.id = pc.category_id WHERE pc.country_id = :country AND pc.category_id = :category LIMIT 1');
$stmt->execute(['country' => $countryId, 'category' => $categoryId]);
$row = $stmt->fetch();

if (!$row) {
    json_response(false, 'Fiyat kaydı bulunamadı');
}

json_response(true, 'Fiyat hesaplandı', $row);
