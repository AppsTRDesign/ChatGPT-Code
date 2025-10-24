<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Helpers::requireAjax();
Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();

$limit = max(1, (int) ($_GET['limit'] ?? 10));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['title', 'created_at', 'delivered', 'clicked', 'dismissed'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'created_at';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE wn.title LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$countSql = 'SELECT COUNT(*) FROM web_notifications wn ' . $where;
$countStmt = $db->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue(':' . $key, $value);
}
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();

$sql = "SELECT wn.id, wn.title, wn.languages_json, wn.platforms_json, wn.created_at,
               SUM(CASE WHEN we.action = 'delivered' THEN 1 ELSE 0 END) AS delivered,
               SUM(CASE WHEN we.action = 'clicked' THEN 1 ELSE 0 END) AS clicked,
               SUM(CASE WHEN we.action = 'dismissed' THEN 1 ELSE 0 END) AS dismissed
        FROM web_notifications wn
        LEFT JOIN web_notification_events we ON we.notification_id = wn.id
        $where
        GROUP BY wn.id
        ORDER BY $sort $order
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
$stmt->execute();

$rows = [];
while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
    $rows[] = [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'languages' => $row['languages_json'],
        'platforms' => $row['platforms_json'],
        'delivered' => (int) $row['delivered'],
        'clicked' => (int) $row['clicked'],
        'dismissed' => (int) $row['dismissed'],
        'created_at' => $row['created_at'],
    ];
}

$filtersStmt = $db->query('SELECT id, title FROM web_notifications ORDER BY created_at DESC LIMIT 200');
$filters = [];
foreach ($filtersStmt->fetchAll(\PDO::FETCH_ASSOC) as $filterRow) {
    $filters[] = [
        'id' => (int) $filterRow['id'],
        'title' => $filterRow['title'],
    ];
}

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $total,
    'rows' => $rows,
    'filters' => $filters,
]);
