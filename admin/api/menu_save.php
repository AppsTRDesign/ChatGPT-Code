<?php
require_once __DIR__ . '/_init.php';

$id = (int)($_POST['id'] ?? 0);
$itemType = $_POST['item_type'] ?? 'page';
$pageId = !empty($_POST['page_id']) ? (int)$_POST['page_id'] : null;
$systemKey = trim((string)($_POST['system_key'] ?? '')) ?: null;
$title = trim((string)($_POST['title'] ?? '')) ?: null;
$url = trim((string)($_POST['url'] ?? '')) ?: null;
$parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

if (!in_array($itemType, ['page','system','custom'], true)) json_response(false,'Geçersiz menü tipi');
if ($itemType === 'page' && !$pageId) json_response(false,'Sayfa seçimi zorunlu');
if ($itemType === 'system' && !$systemKey) json_response(false,'Sistem linki seçimi zorunlu');
if ($itemType === 'custom' && !$url) json_response(false,'Özel bağlantı URL zorunlu');

if ($id > 0) {
    if ($parentId === $id) json_response(false,'Menü kendi altına taşınamaz');
    $stmt = db()->prepare('UPDATE menus SET item_type=:item_type,page_id=:page_id,system_key=:system_key,title=:title,url=:url,parent_id=:parent_id,is_active=:is_active WHERE id=:id');
    $stmt->execute([
        'item_type'=>$itemType,
        'page_id'=>$itemType==='page' ? $pageId : null,
        'system_key'=>$itemType==='system' ? $systemKey : null,
        'title'=>$title,
        'url'=>$itemType==='custom' ? $url : null,
        'parent_id'=>$parentId,
        'is_active'=>$isActive,
        'id'=>$id,
    ]);
    json_response(true,'Menü güncellendi');
}

$nextSort = (int)db()->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM menus WHERE '.($parentId ? 'parent_id='.(int)$parentId : 'parent_id IS NULL'))->fetchColumn();
$stmt = db()->prepare('INSERT INTO menus(item_type,page_id,system_key,title,url,parent_id,sort_order,is_active) VALUES(:item_type,:page_id,:system_key,:title,:url,:parent_id,:sort_order,:is_active)');
$stmt->execute([
    'item_type'=>$itemType,
    'page_id'=>$itemType==='page' ? $pageId : null,
    'system_key'=>$itemType==='system' ? $systemKey : null,
    'title'=>$title,
    'url'=>$itemType==='custom' ? $url : null,
    'parent_id'=>$parentId,
    'sort_order'=>$nextSort,
    'is_active'=>$isActive,
]);
json_response(true,'Menü eklendi');
