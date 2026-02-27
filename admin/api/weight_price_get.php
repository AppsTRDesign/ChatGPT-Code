<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT id,transport_mode_id,weight_limit,price_amount FROM transport_mode_weight_prices WHERE id=:id');
$stmt->execute(['id'=>$id]);
$row=$stmt->fetch();
if(!$row) json_response(false,'Kayıt bulunamadı');
json_response(true,'ok',$row);
