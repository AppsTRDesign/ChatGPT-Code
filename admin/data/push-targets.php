<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();

$limit = max(1, (int) ($_GET['limit'] ?? 10));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim((string) ($_GET['search'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'last_active');
$order = strtoupper((string) ($_GET['order'] ?? 'DESC'));

$columnMap = [
    'username' => 'u.username',
    'email' => 'u.email',
    'platform' => 's.platform',
    'language' => 's.language',
    'country' => 's.country',
    'last_active' => 's.last_active',
];

if (!isset($columnMap[$sort])) {
    $sort = 'last_active';
}

if ($order !== 'ASC' && $order !== 'DESC') {
    $order = 'DESC';
}

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(u.username LIKE :search OR u.email LIKE :search OR s.player_id LIKE :search OR s.platform LIKE :search OR s.language LIKE :search OR s.country LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*)
    FROM onesignal_subscriptions s
    LEFT JOIN users u ON u.id = s.external_id
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
            s.last_active,
            u.username,
            u.email
        FROM onesignal_subscriptions s
        LEFT JOIN users u ON u.id = s.external_id
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

$data = array_map(static function (array $row) {
    $hasUser = !empty($row['external_id']);
    $username = $row['username'] ?? '';
    if ($username === '' && $hasUser) {
        $username = 'Üye #' . (int) $row['external_id'];
    }

    if ($username === '') {
        $username = 'Ziyaretçi';
    }

    $email = $row['email'] ?? '';
    $country = $row['country'] ?? '';
    $language = $row['language'] ?? '';

    return [
        'id' => $hasUser ? ('user:' . (int) $row['external_id']) : ('player:' . $row['player_id']),
        'user_id' => $hasUser ? (int) $row['external_id'] : null,
        'player_id' => $row['player_id'],
        'username' => $username,
        'email' => $email,
        'platform' => $row['platform'] ?? '',
        'language' => $language,
        'country' => $country,
        'last_active' => $row['last_active'],
        'guest' => !$hasUser,
        'checkDisabled' => $hasUser ? false : true,
    ];
}, $rows);

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $data,
]);
