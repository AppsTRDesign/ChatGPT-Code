<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$code = strtolower(trim((string)($_GET['code'] ?? '')));
$stmt = db()->prepare('SELECT code,name,is_active,sort_order FROM languages WHERE code=:c LIMIT 1');
$stmt->execute(['c'=>$code]);
$row = $stmt->fetch();
if(!$row) json_response(false,'Dil bulunamadı');
json_response(true,'ok',$row);
