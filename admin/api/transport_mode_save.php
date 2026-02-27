<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_POST['mode_id']??0);
$key=trim((string)($_POST['mode_key']??''));
$title=trim((string)($_POST['title']??''));
$mult=(float)($_POST['multiplier']??1);
$active=isset($_POST['is_active'])?(int)$_POST['is_active']:1;
if($key===''||$title==='') json_response(false,'Mod anahtarı ve başlık gerekli');
if($id>0){
 db()->prepare('UPDATE transport_modes SET mode_key=:k,title=:t,multiplier=:m,is_active=:a WHERE id=:id')->execute(['k'=>$key,'t'=>$title,'m'=>$mult,'a'=>$active,'id'=>$id]);
 json_response(true,'Taşıma modu güncellendi');
}
db()->prepare('INSERT INTO transport_modes(mode_key,title,multiplier,is_active) VALUES(:k,:t,:m,:a)')->execute(['k'=>$key,'t'=>$title,'m'=>$mult,'a'=>$active]);
json_response(true,'Taşıma modu kaydedildi');
