<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT id,mode_key,title,multiplier,is_active FROM transport_modes WHERE id=:id');
$stmt->execute(['id'=>$id]);
$r=$stmt->fetch();
if(!$r) json_response(false,'Taşıma modu bulunamadı');
json_response(true,'ok',$r);
