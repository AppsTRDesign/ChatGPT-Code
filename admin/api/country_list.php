<?php
require_once __DIR__ . '/_init.php';
$rows = db()->query('SELECT id,name,currency_code,currency_symbol,is_active FROM countries ORDER BY id DESC')->fetchAll();
json_response(true,'ok',$rows);
