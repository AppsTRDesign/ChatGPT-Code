<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';
if (!admin_auth()) json_response(false, 'Yetkisiz');

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int)($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;
$where = '';
$params = [];
if ($q !== '') {
    $where = ' WHERE email LIKE :q ';
    $params['q'] = "%$q%";
}
$c = db()->prepare('SELECT COUNT(*) FROM subscribers' . $where);
$c->execute($params);
$total = (int)$c->fetchColumn();
$sql = 'SELECT id,email,created_at FROM subscribers' . $where . ' ORDER BY id DESC LIMIT :l OFFSET :o';
$s = db()->prepare($sql);
foreach($params as $k=>$v){ $s->bindValue(':'.$k, $v, PDO::PARAM_STR); }
$s->bindValue(':l', $perPage, PDO::PARAM_INT);
$s->bindValue(':o', $offset, PDO::PARAM_INT);
$s->execute();
json_response(true,'ok',[
  'rows'=>$s->fetchAll(),
  'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total,'total_pages'=>(int)max(1,ceil($total/$perPage))]
]);
