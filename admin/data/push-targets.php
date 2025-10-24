<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if (!Helpers::tableExists('users')) {
    echo json_encode([
        'total' => 0,
        'totalNotFiltered' => 0,
        'rows' => [],
    ]);
    return;
}

$db = Helpers::db();

$limit = max(1, (int) ($_GET['limit'] ?? 10));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim((string) ($_GET['search'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'created_at');
$order = strtoupper((string) ($_GET['order'] ?? 'DESC'));

$columnMap = [
    'id' => 'u.id',
    'username' => 'u.username',
    'email' => 'u.email',
    'created_at' => 'u.created_at',
];

if (!isset($columnMap[$sort])) {
    $sort = 'created_at';
}

if ($order !== 'ASC' && $order !== 'DESC') {
    $order = 'DESC';
}

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(u.username LIKE :search OR u.email LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalAll = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

$sql = "SELECT u.id, u.username, u.email, u.created_at FROM users u $whereSql ORDER BY {$columnMap[$sort]} $order LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
$stmt->execute();

$rows = array_map(static function (array $row) {
    return [
        'id' => 'user:' . $row['id'],
        'external_id' => (int) $row['id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'created_at' => $row['created_at'],
    ];
}, $stmt->fetchAll());

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $rows,
]);
