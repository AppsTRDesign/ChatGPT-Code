<?php

declare(strict_types=1);
require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$lang = trim((string)($_GET['lang'] ?? 'en'));
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int)($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;

$where = ' WHERE lang_code = :lang ';
$params = ['lang' => $lang];
if ($q !== '') {
    $where .= ' AND (group_name LIKE :q OR key_name LIKE :q OR text_value LIKE :q) ';
    $params['q'] = '%' . $q . '%';
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM translations' . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = 'SELECT id, lang_code, group_name, key_name, text_value
        FROM translations' . $where . '
        ORDER BY group_name, key_name
        LIMIT :limit OFFSET :offset';
$stmt = db()->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

json_response(true, 'ok', [
    'rows' => $stmt->fetchAll(),
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int)max(1, ceil($total / $perPage)),
    ],
]);
