<?php require_once __DIR__ . '/_init.php';
$keys=['site_name','company_name','company_email','company_phone','company_address','meta_title','meta_description','logo_path','favicon_path','osm_embed_url'];
$stmt=db()->prepare('INSERT INTO settings(key_name, value) VALUES(:k,:v) ON DUPLICATE KEY UPDATE value=VALUES(value)');
foreach($keys as $k){$stmt->execute(['k'=>$k,'v'=>trim($_POST[$k]??'')]);}
json_response(true,'Ayarlar kaydedildi');
