<?php require_once __DIR__ . '/_init.php';
$slug = seo_slug(trim($_POST['slug_source'] ?? ''));
$lang = trim($_POST['lang_code'] ?? DEFAULT_LANG);
$title = trim($_POST['title'] ?? '');
$content = $_POST['content_html'] ?? '';
$pdo = db();
$pdo->beginTransaction();
try {
  $pdo->prepare('INSERT INTO pages(slug,is_active,created_at,updated_at) VALUES(:slug,1,NOW(),NOW())')->execute(['slug'=>$slug]);
  $pageId = (int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO page_translations(page_id,lang_code,title,content_html) VALUES(:pid,:lang,:title,:content)')->execute(['pid'=>$pageId,'lang'=>$lang,'title'=>$title,'content'=>$content]);
  $pdo->commit();
  json_response(true,'Sayfa kaydedildi',['page_id'=>$pageId,'slug'=>$slug]);
} catch(Throwable $e){$pdo->rollBack();json_response(false,$e->getMessage());}
