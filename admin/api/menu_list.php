<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');

$sql = "SELECT m.id,m.item_type,m.page_id,m.system_key,m.title,m.url,m.parent_id,m.sort_order,m.is_active,
COALESCE(pt.title,p.slug) AS page_title
FROM menus m
LEFT JOIN pages p ON p.id=m.page_id
LEFT JOIN page_translations pt ON pt.page_id=p.id AND pt.lang_code='en'
ORDER BY COALESCE(m.parent_id,0), m.sort_order, m.id";
$rows = db()->query($sql)->fetchAll();
foreach($rows as &$r){
    if (($r['item_type'] ?? '') === 'page' && empty($r['title'])) {
        $r['title'] = $r['page_title'] ?? 'Page #' . (int)($r['page_id'] ?? 0);
    }
}
unset($r);
json_response(true,'ok',$rows);
