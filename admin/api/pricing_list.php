<?php
require_once __DIR__ . '/_init.php';
$rows = db()->query('SELECT pc.id,c.name country_name,c.currency_code,c.currency_symbol,cat.title category_title,tm.mode_key,tm.title mode_title,tm.multiplier,cat.divisor,pc.weight_prices_json FROM price_configs pc JOIN countries c ON c.id = pc.country_id JOIN price_categories cat ON cat.id = pc.category_id JOIN transport_modes tm ON tm.id = pc.transport_mode_id ORDER BY c.name, cat.title, tm.title')->fetchAll();
json_response(true,'ok',$rows);
