<?php require_once __DIR__ . '/_init.php';
$pageId = (int)($_POST['page_id'] ?? 0);
$lang = trim((string)($_POST['lang_code'] ?? DEFAULT_LANG));
$title = trim((string)($_POST['title'] ?? ''));
$content = (string)($_POST['content_html'] ?? '');
if ($title === '') json_response(false,'Başlık gerekli');
$slug = seo_slug($title);
$pdo = db();
$pdo->beginTransaction();
try {
  if ($pageId > 0) {
    $pdo->prepare('UPDATE pages SET slug=:slug,updated_at=NOW() WHERE id=:id')->execute(['slug'=>$slug,'id'=>$pageId]);
  } else {
    $pdo->prepare('INSERT INTO pages(slug,is_active,created_at,updated_at) VALUES(:slug,1,NOW(),NOW())')->execute(['slug'=>$slug]);
    $pageId = (int)$pdo->lastInsertId();
  }

  $pdo->prepare('INSERT INTO page_translations(page_id,lang_code,title,content_html) VALUES(:pid,:lang,:title,:content)
    ON DUPLICATE KEY UPDATE title=VALUES(title), content_html=VALUES(content_html)')
    ->execute(['pid'=>$pageId,'lang'=>$lang,'title'=>$title,'content'=>$content]);

  $pdo->commit();
  json_response(true,'Sayfa kaydedildi',['page_id'=>$pageId,'slug'=>$slug]);
} catch(Throwable $e){
  $pdo->rollBack();
  json_response(false,$e->getMessage());
}
