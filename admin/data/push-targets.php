<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

$subscriptionsExist = Helpers::tableExists('onesignal_subscriptions');
if (!$subscriptionsExist) {
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
$sort = (string) ($_GET['sort'] ?? 'last_active');
$order = strtoupper((string) ($_GET['order'] ?? 'DESC'));

$hasUsersTable = Helpers::tableExists('users');

$columnMap = [
    'platform' => 's.platform',
    'language' => 's.language',
    'country' => 's.country',
    'last_active' => 's.last_active',
    'player_id' => 's.player_id',
];

if ($hasUsersTable) {
    $columnMap['username'] = 'u.username';
    $columnMap['email'] = 'u.email';
}

if (!isset($columnMap[$sort])) {
    $sort = 'last_active';
}

if ($order !== 'ASC' && $order !== 'DESC') {
    $order = 'DESC';
}

$conditions = [];
$params = [];

if ($search !== '') {
    $searchColumns = ['s.player_id', 's.platform', 's.language', 's.country'];
    if ($hasUsersTable) {
        $searchColumns[] = 'u.username';
        $searchColumns[] = 'u.email';
    }
    $likeParts = array_map(static fn(string $column) => "$column LIKE :search", $searchColumns);
    $conditions[] = '(' . implode(' OR ', $likeParts) . ')';
    $params['search'] = '%' . $search . '%';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$join = $hasUsersTable ? 'LEFT JOIN users u ON u.id = s.external_id' : '';

$countSql = "SELECT COUNT(*)
    FROM onesignal_subscriptions s
    $join
    $where";

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalAll = (int) $db->query('SELECT COUNT(*) FROM onesignal_subscriptions')->fetchColumn();

$sql = "SELECT
            s.id AS subscription_id,
            s.player_id,
            s.external_id,
            s.platform,
            s.language,
            s.country,
            s.last_active" . ($hasUsersTable ? ', u.username, u.email' : '') . "
        FROM onesignal_subscriptions s
        $join
        $where
        ORDER BY {$columnMap[$sort]} $order
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}

$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll();

$data = array_map(static function (array $row) use ($hasUsersTable) {
    $externalId = $row['external_id'] ?? null;
    $hasUser = $externalId !== null && $externalId !== '';

    $username = $hasUsersTable ? ($row['username'] ?? '') : '';
    if ($username === '' && $hasUser) {
        $username = 'Üye #' . (int) $externalId;
    }

    if ($username === '') {
        $username = 'Ziyaretçi';
    }

    $email = $hasUsersTable ? ($row['email'] ?? '') : '';

    $country = $row['country'] ?? '';
    $language = $row['language'] ?? '';

    return [
        'id' => 'player:' . $row['player_id'],
        'external_id' => $hasUser ? (int) $externalId : null,
        'player_id' => $row['player_id'],
        'username' => $username,
        'email' => $email,
        'platform' => $row['platform'] ?? '',
        'language' => $language,
        'country' => $country,
        'last_active' => $row['last_active'],
        'guest' => !$hasUser,
    ];
}, $rows);

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $data,
]);
