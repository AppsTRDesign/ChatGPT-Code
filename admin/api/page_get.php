<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false,'Yetkisiz');
$id = (int)($_GET['id'] ?? 0);
$lang = trim((string)($_GET['lang'] ?? DEFAULT_LANG));
$stmt = db()->prepare('SELECT p.id,p.slug,pt.lang_code,pt.title,pt.content_html FROM pages p LEFT JOIN page_translations pt ON pt.page_id=p.id AND pt.lang_code=:lang WHERE p.id=:id LIMIT 1');
$stmt->execute(['id'=>$id,'lang'=>$lang]);
$row = $stmt->fetch();
if(!$row){ json_response(false,'Sayfa bulunamadı'); }
if (empty($row['title'])) {
    $stmt = db()->prepare('SELECT lang_code,title,content_html FROM page_translations WHERE page_id=:id ORDER BY (lang_code=:def) DESC, lang_code LIMIT 1');
    $stmt->execute(['id'=>$id,'def'=>DEFAULT_LANG]);
    $tr = $stmt->fetch();
    if($tr){
      $row['lang_code']=$tr['lang_code'];
      $row['title']=$tr['title'];
      $row['content_html']=$tr['content_html'];
    }
}
json_response(true,'ok',$row);
