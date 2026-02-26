<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

try {
    $stmt = db()->query('SELECT tracking_number, origin_country, destination_country, current_latitude, current_longitude FROM shipments WHERE current_latitude IS NOT NULL AND current_longitude IS NOT NULL ORDER BY updated_at DESC LIMIT 500');
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    $rows = [];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
