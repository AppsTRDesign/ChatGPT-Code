<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_POST['id']??0);
if($id<=0) json_response(false,'ID gerekli');
db()->prepare('DELETE FROM shipments WHERE id=:id')->execute(['id'=>$id]);
json_response(true,'Kargo silindi');
