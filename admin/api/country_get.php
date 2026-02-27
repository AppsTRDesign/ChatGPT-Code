<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT id,name,currency_code,currency_symbol,is_active FROM countries WHERE id=:id LIMIT 1');
$stmt->execute(['id'=>$id]);
$row=$stmt->fetch();
if(!$row) json_response(false,'Ülke bulunamadı');
json_response(true,'ok',$row);
