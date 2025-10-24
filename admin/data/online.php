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
$sort = $_GET['sort'] ?? 'last_seen';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['last_seen', 'country', 'city', 'platform'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'last_seen';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$window = max(1, (int) ($_GET['window'] ?? 5));

$where = 'WHERE sa.last_seen >= (NOW() - INTERVAL ' . $window . ' MINUTE)';
$params = [];

if ($search !== '') {
    $where .= ' AND (u.username LIKE :search OR sa.ip LIKE :search OR sa.referer LIKE :search OR sa.country LIKE :search OR sa.city LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$countSql = "SELECT COUNT(*) FROM session_activity sa
    LEFT JOIN users u ON u.id = sa.user_id
    $where";

$countStmt = $db->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();

$totalAll = (int) $db->query('SELECT COUNT(*) FROM session_activity WHERE last_seen >= (NOW() - INTERVAL ' . $window . ' MINUTE)')->fetchColumn();

$sql = "SELECT sa.session_key, sa.user_id, sa.ip, sa.platform, sa.country, sa.city, sa.referer, sa.search_engine, sa.search_term, sa.last_url, sa.last_seen, u.username
        FROM session_activity sa
        LEFT JOIN users u ON u.id = sa.user_id
        $where
        ORDER BY $sort $order
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = array_map(static function (array $row): array {
    return [
        'session_key' => $row['session_key'],
        'username' => $row['username'] ?? 'Ziyaretçi',
        'platform' => $row['platform'] ?? 'Bilinmiyor',
        'ip' => $row['ip'],
        'country' => $row['country'],
        'city' => $row['city'],
        'referer' => $row['referer'],
        'search_engine' => $row['search_engine'],
        'search_term' => $row['search_term'],
        'last_url' => $row['last_url'],
        'last_seen' => $row['last_seen'],
    ];
}, $stmt->fetchAll());

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $rows,
]);
