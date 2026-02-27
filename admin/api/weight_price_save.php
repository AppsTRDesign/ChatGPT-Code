<?php
require_once __DIR__ . '/_init.php';

$id=(int)($_POST['id']??0);
$modeId=(int)($_POST['transport_mode_id']??0);
$weight=(float)($_POST['weight_limit']??0);
$price=(float)($_POST['price_amount']??0);
if($modeId<1 || $weight<=0 || $price<0) json_response(false,'Taşıma modu, kilo ve fiyat zorunlu');

if($id>0){
    db()->prepare('UPDATE transport_mode_weight_prices SET transport_mode_id=:m, weight_limit=:w, price_amount=:p WHERE id=:id')
        ->execute(['m'=>$modeId,'w'=>$weight,'p'=>$price,'id'=>$id]);
    json_response(true,'Ağırlık fiyatı güncellendi');
}

db()->prepare('INSERT INTO transport_mode_weight_prices(transport_mode_id,weight_limit,price_amount) VALUES(:m,:w,:p) ON DUPLICATE KEY UPDATE price_amount=VALUES(price_amount)')
    ->execute(['m'=>$modeId,'w'=>$weight,'p'=>$price]);
json_response(true,'Ağırlık fiyatı kaydedildi');
