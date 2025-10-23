<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();

$limit = max(1, (int) ($_GET['limit'] ?? 10));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = [
    'name',
    'monthly_limit',
    'duration_days',
    'price',
    'is_active',
    'created_at',
    'updated_at',
];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'created_at';
}

if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = '';
$params = [];

if ($search !== '') {
    $where = 'WHERE name LIKE :search OR description LIKE :search OR features LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM packages $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalAll = (int) $db->query('SELECT COUNT(*) FROM packages')->fetchColumn();

$sql = "SELECT id, name, description, monthly_limit, duration_days, features, price, is_active, created_at, updated_at
        FROM packages
        $where
        ORDER BY $sort $order
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}

$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll();

$data = array_map(static function (array $row) {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'monthly_limit' => (int) $row['monthly_limit'],
        'duration_days' => (int) $row['duration_days'],
        'features' => $row['features'],
        'price' => (float) $row['price'],
        'is_active' => (int) $row['is_active'] === 1,
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'] ?? null,
    ];
}, $rows);

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $data,
]);
