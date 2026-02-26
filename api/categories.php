<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$countryId = (int) ($_GET['country_id'] ?? 0);
$stmt = db()->prepare('SELECT cat.id, cat.title FROM price_categories cat JOIN price_configs pc ON pc.category_id = cat.id WHERE pc.country_id = :cid GROUP BY cat.id, cat.title ORDER BY cat.title');
$stmt->execute(['cid' => $countryId]);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
