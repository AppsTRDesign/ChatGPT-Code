<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$lang = current_lang();
$sql = 'SELECT s.id,s.tracking_number,
COALESCE(oc_t.name, oc.name, s.origin_country) AS origin_country,
COALESCE(dc_t.name, dc.name, s.destination_country) AS destination_country,
s.current_status,s.updated_at
FROM shipments s
LEFT JOIN countries oc ON oc.id = s.origin_country_id
LEFT JOIN country_translations oc_t ON oc_t.country_id = oc.id AND oc_t.lang_code = :lang
LEFT JOIN countries dc ON dc.id = s.destination_country_id
LEFT JOIN country_translations dc_t ON dc_t.country_id = dc.id AND dc_t.lang_code = :lang2
ORDER BY s.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute(['lang'=>$lang,'lang2'=>$lang]);
$rows = $stmt->fetchAll();
json_response(true,'ok',$rows);
