<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$rows=db()->query("SELECT m.id,m.page_id,m.sort_order,COALESCE(pt.title,p.slug) title FROM menus m JOIN pages p ON p.id=m.page_id LEFT JOIN page_translations pt ON pt.page_id=p.id AND pt.lang_code='en' ORDER BY m.sort_order,m.id")->fetchAll();
json_response(true,'ok',$rows);
