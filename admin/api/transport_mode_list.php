<?php
require_once __DIR__ . '/_init.php';
$rows=db()->query('SELECT id,mode_key,title,multiplier,is_active FROM transport_modes ORDER BY id DESC')->fetchAll();
json_response(true,'ok',$rows);
