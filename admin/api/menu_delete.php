<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_POST['id']??0);
if($id<1) json_response(false,'id gerekli');
$stmt=db()->prepare('DELETE FROM menus WHERE id=:id OR parent_id=:id');
$stmt->execute(['id'=>$id]);
json_response(true,'Menü silindi');
