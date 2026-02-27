<?php
require_once __DIR__ . '/_init.php';
$menuId=(int)($_POST['menu_id']??0);
$lang=trim((string)($_POST['lang_code']??''));
$title=trim((string)($_POST['title']??''));
if($menuId<1||$lang===''||$title==='') json_response(false,'Menü, dil ve başlık gerekli');
db()->prepare('INSERT INTO menu_translations(menu_id,lang_code,title) VALUES(:m,:l,:t) ON DUPLICATE KEY UPDATE title=VALUES(title)')->execute(['m'=>$menuId,'l'=>$lang,'t'=>$title]);
json_response(true,'Menü çevirisi kaydedildi');
