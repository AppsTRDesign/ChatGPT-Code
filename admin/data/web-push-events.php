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
$campaignId = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : null;

$allowedSort = ['created_at', 'event_type', 'platform', 'country', 'city'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'created_at';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = 'WHERE 1=1';
$params = [];

if ($campaignId) {
    $where .= ' AND e.campaign_id = :campaign_id';
    $params['campaign_id'] = $campaignId;
}

if ($search !== '') {
    $where .= ' AND (c.title LIKE :search OR e.ip LIKE :search OR e.referer LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$countSql = "SELECT COUNT(*) FROM web_push_events e
    INNER JOIN web_push_campaigns c ON c.id = e.campaign_id
    $where";

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalAll = (int) $db->query('SELECT COUNT(*) FROM web_push_events')->fetchColumn();

$sql = "SELECT e.id, e.campaign_id, c.title, e.event_type, e.platform, e.ip, e.country, e.city, e.referer, e.search_engine, e.search_term, e.created_at
        FROM web_push_events e
        INNER JOIN web_push_campaigns c ON c.id = e.campaign_id
        $where
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
        'campaign_id' => (int) $row['campaign_id'],
        'title' => $row['title'],
        'event_type' => $row['event_type'],
        'platform' => $row['platform'],
        'ip' => $row['ip'],
        'country' => $row['country'],
        'city' => $row['city'],
        'referer' => $row['referer'],
        'search_engine' => $row['search_engine'],
        'search_term' => $row['search_term'],
        'created_at' => $row['created_at'],
    ];
}, $stmt->fetchAll());

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $rows,
]);
