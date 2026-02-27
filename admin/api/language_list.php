<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$rows=db()->query('SELECT code,name,is_active,sort_order FROM languages ORDER BY sort_order,code')->fetchAll();
json_response(true,'ok',$rows);
