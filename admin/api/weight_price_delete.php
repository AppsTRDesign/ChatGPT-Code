<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_POST['id']??0);
if($id<1) json_response(false,'id gerekli');
db()->prepare('DELETE FROM transport_mode_weight_prices WHERE id=:id')->execute(['id'=>$id]);
json_response(true,'Ağırlık fiyatı silindi');
