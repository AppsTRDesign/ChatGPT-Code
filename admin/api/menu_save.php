<?php require_once __DIR__ . '/_init.php';
$stmt=db()->prepare('INSERT INTO menus(page_id,sort_order) VALUES(:pid,:s)');
$stmt->execute(['pid'=>(int)$_POST['page_id'],'s'=>(int)($_POST['sort_order']??1)]);
json_response(true,'Menü eklendi');
