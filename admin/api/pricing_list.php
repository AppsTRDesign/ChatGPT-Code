<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$rows = db()->query('SELECT c.name country_name, cat.title category_title, pc.price_amount FROM price_configs pc JOIN countries c ON c.id = pc.country_id JOIN price_categories cat ON cat.id = pc.category_id ORDER BY c.name, cat.title')->fetchAll();
json_response(true, 'ok', $rows);
