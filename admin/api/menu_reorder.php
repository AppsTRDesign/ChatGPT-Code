<?php
require_once __DIR__ . '/_init.php';
$tree = $_POST['tree'] ?? [];
if(!is_array($tree)) json_response(false,'tree array gerekli');
$stmt = db()->prepare('UPDATE menus SET parent_id=:parent_id, sort_order=:sort_order WHERE id=:id');
foreach($tree as $row){
    if (!is_array($row) || empty($row['id'])) continue;
    $stmt->execute([
        'parent_id'=>!empty($row['parent_id']) ? (int)$row['parent_id'] : null,
        'sort_order'=>(int)($row['sort_order'] ?? 1),
        'id'=>(int)$row['id'],
    ]);
}
json_response(true,'Menü sırası güncellendi');
