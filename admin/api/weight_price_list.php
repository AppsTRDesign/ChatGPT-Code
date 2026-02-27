<?php
require_once __DIR__ . '/_init.php';
$rows=db()->query('SELECT wp.id,wp.transport_mode_id,tm.title mode_title,tm.mode_key,wp.weight_limit,wp.price_amount FROM transport_mode_weight_prices wp JOIN transport_modes tm ON tm.id=wp.transport_mode_id ORDER BY tm.title, wp.weight_limit')->fetchAll();
json_response(true,'ok',$rows);
