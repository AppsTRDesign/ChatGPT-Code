<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$lang = current_lang();
try {
    $stmt = db()->prepare('SELECT s.tracking_number, COALESCE(oc_t.name, oc.name, s.origin_country) AS origin_country, COALESCE(dc_t.name, dc.name, s.destination_country) AS destination_country, s.current_latitude, s.current_longitude FROM shipments s LEFT JOIN countries oc ON oc.id = s.origin_country_id LEFT JOIN country_translations oc_t ON oc_t.country_id = oc.id AND oc_t.lang_code = :lang LEFT JOIN countries dc ON dc.id = s.destination_country_id LEFT JOIN country_translations dc_t ON dc_t.country_id = dc.id AND dc_t.lang_code = :lang2 WHERE s.current_latitude IS NOT NULL AND s.current_longitude IS NOT NULL ORDER BY s.updated_at DESC LIMIT 500');
    $stmt->execute(['lang'=>$lang,'lang2'=>$lang]);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    $rows = [];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
