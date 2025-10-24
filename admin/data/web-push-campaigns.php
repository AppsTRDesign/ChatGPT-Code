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
$sort = $_GET['sort'] ?? 'sent_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['title', 'sent_at', 'audience', 'delivered', 'viewed', 'clicked'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'sent_at';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE c.title LIKE :search OR c.message LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM web_push_campaigns c $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalAll = (int) $db->query('SELECT COUNT(*) FROM web_push_campaigns')->fetchColumn();

$sql = "SELECT
            c.id,
            c.title,
            c.audience,
            c.status,
            c.sent_at,
            COALESCE(SUM(CASE WHEN e.event_type = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered,
            COALESCE(SUM(CASE WHEN e.event_type = 'viewed' THEN 1 ELSE 0 END), 0) AS viewed,
            COALESCE(SUM(CASE WHEN e.event_type = 'clicked' THEN 1 ELSE 0 END), 0) AS clicked
        FROM web_push_campaigns c
        LEFT JOIN web_push_events e ON e.campaign_id = c.id
        $where
        GROUP BY c.id
        ORDER BY $sort $order
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = array_map(static function (array $row): array {
    return [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'audience' => $row['audience'],
        'status' => $row['status'],
        'sent_at' => $row['sent_at'],
        'delivered' => (int) $row['delivered'],
        'viewed' => (int) $row['viewed'],
        'clicked' => (int) $row['clicked'],
    ];
}, $stmt->fetchAll());

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $rows,
]);
