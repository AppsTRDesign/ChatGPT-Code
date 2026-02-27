<?php
require_once __DIR__ . '/_init.php';
$country=(int)($_POST['country_id']??0);
$category=(int)($_POST['category_id']??0);
$mode=(int)($_POST['transport_mode_id']??0);
if($country<1||$category<1||$mode<1) json_response(false,'Ülke, kategori ve taşıma modu zorunlu');

db()->prepare('INSERT INTO price_configs(country_id,category_id,transport_mode_id,weight_prices_json) VALUES(:c,:k,:m,:j) ON DUPLICATE KEY UPDATE transport_mode_id=VALUES(transport_mode_id)')
  ->execute(['c'=>$country,'k'=>$category,'m'=>$mode,'j'=>'{}']);
json_response(true,'Fiyat stratejisi bağlantısı kaydedildi');
