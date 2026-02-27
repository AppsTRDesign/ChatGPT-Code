<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$rows = db()->query('SELECT id,tracking_number,origin_country,destination_country,current_status,updated_at FROM shipments ORDER BY id DESC')->fetchAll();
json_response(true,'ok',$rows);
