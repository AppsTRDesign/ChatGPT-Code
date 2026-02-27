<?php
require_once __DIR__ . '/_init.php';
$country=(int)($_POST['country_id']??0);
$category=(int)($_POST['category_id']??0);
$tableText=trim((string)($_POST['weight_prices']??''));
if($country<1||$category<1) json_response(false,'Ülke ve kategori zorunlu');
if($tableText==='') json_response(false,'Kilo fiyat tablosu zorunlu');
$map=[];
foreach(preg_split('/\r?\n/',$tableText) as $line){
    $line=trim($line); if($line==='') continue;
    [$kg,$price]=array_map('trim',explode(':',$line)+['','']);
    if(!is_numeric($kg)||!is_numeric($price)) continue;
    $map[(float)$kg]=(float)$price;
}
if(!$map) json_response(false,'Geçerli tablo girin (örn 1:79)');
ksort($map);
$json=json_encode($map, JSON_UNESCAPED_UNICODE);
db()->prepare('INSERT INTO price_configs(country_id,category_id,weight_prices_json) VALUES(:c,:k,:j) ON DUPLICATE KEY UPDATE weight_prices_json=VALUES(weight_prices_json)')->execute(['c'=>$country,'k'=>$category,'j'=>$json]);
json_response(true,'Fiyat stratejisi kaydedildi');
