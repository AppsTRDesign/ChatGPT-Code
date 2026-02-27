<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$rows=db()->query("SELECT p.id,p.slug,p.updated_at,COALESCE(pt.title,p.slug) title FROM pages p LEFT JOIN page_translations pt ON pt.page_id=p.id AND pt.lang_code='en' ORDER BY p.id DESC")->fetchAll();
json_response(true,'ok',$rows);
