<?php
require_once __DIR__ . '/_init.php';
$order=$_POST['order']??[];
if(!is_array($order)) json_response(false,'order array gerekli');
$stmt=db()->prepare('UPDATE menus SET sort_order=:s WHERE id=:id');
$i=1;foreach($order as $id){$stmt->execute(['s'=>$i++,'id'=>(int)$id]);}
json_response(true,'Menü sırası güncellendi');
