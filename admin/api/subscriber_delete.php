<?php
require_once __DIR__ . '/_init.php';
$id=(int)($_POST['id'] ?? 0);
if($id<1) json_response(false,'Geçersiz ID');
db()->prepare('DELETE FROM subscribers WHERE id=:id')->execute(['id'=>$id]);
json_response(true,'Abone silindi');
