<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT id,slug FROM pages WHERE id=:id LIMIT 1');
$stmt->execute(['id'=>$id]);
$page = $stmt->fetch();
if(!$page){ json_response(false,'Sayfa bulunamadı'); }

$trStmt = db()->prepare('SELECT lang_code,title,content_html FROM page_translations WHERE page_id=:id ORDER BY lang_code');
$trStmt->execute(['id'=>$id]);
$rows=$trStmt->fetchAll();
$translations=[];
foreach($rows as $r){
  $translations[$r['lang_code']] = ['title'=>$r['title'],'content_html'=>$r['content_html']];
}

json_response(true,'ok',['id'=>$page['id'],'slug'=>$page['slug'],'translations'=>$translations]);
