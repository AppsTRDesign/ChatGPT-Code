<?php
require_once __DIR__ . '/_init.php';

$pageId = (int)($_POST['page_id'] ?? 0);
$translationsJson = trim((string)($_POST['translations_json'] ?? ''));
$translations = json_decode($translationsJson, true);
if (!is_array($translations) || !$translations) {
    json_response(false, 'Çeviri verisi gerekli');
}

$enTitle = trim((string)($translations['en']['title'] ?? ''));
if ($enTitle === '') {
    foreach ($translations as $t) {
        $candidate = trim((string)($t['title'] ?? ''));
        if ($candidate !== '') {
            $enTitle = $candidate;
            break;
        }
    }
}
if ($enTitle === '') {
    json_response(false, 'En az bir dilde başlık gerekli');
}

$slug = seo_slug($enTitle);
$pdo = db();
$pdo->beginTransaction();
try {
    if ($pageId > 0) {
        $pdo->prepare('UPDATE pages SET slug=:slug,updated_at=NOW() WHERE id=:id')->execute(['slug'=>$slug,'id'=>$pageId]);
    } else {
        $pdo->prepare('INSERT INTO pages(slug,is_active,created_at,updated_at) VALUES(:slug,1,NOW(),NOW())')->execute(['slug'=>$slug]);
        $pageId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('INSERT INTO page_translations(page_id,lang_code,title,content_html) VALUES(:pid,:lang,:title,:content)
      ON DUPLICATE KEY UPDATE title=VALUES(title), content_html=VALUES(content_html)');

    foreach ($translations as $lang => $data) {
        $title = trim((string)($data['title'] ?? ''));
        $content = (string)($data['content_html'] ?? '');
        if ($title === '' && trim(strip_tags($content)) === '') {
            continue;
        }
        $stmt->execute([
            'pid' => $pageId,
            'lang' => (string)$lang,
            'title' => $title !== '' ? $title : $enTitle,
            'content' => $content,
        ]);
    }

    $pdo->commit();
    json_response(true,'Sayfa kaydedildi',['page_id'=>$pageId,'slug'=>$slug]);
} catch(Throwable $e){
    $pdo->rollBack();
    json_response(false,$e->getMessage());
}
