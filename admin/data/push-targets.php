<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\Settings;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if (!Settings::onesignalEnabled() || !Helpers::tableExists('onesignal_subscriptions')) {
    echo json_encode([
        'total' => 0,
        'rows' => [],
    ]);
    exit;
}

$db = Helpers::db();

$limit = max(1, (int) ($_GET['limit'] ?? 10));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim((string) ($_GET['search'] ?? ''));
$sort = $_GET['sort'] ?? 'last_active';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['player_id', 'external_id', 'country', 'city', 'device_type', 'last_active'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'last_active';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = '';
$params = [];
if ($search !== '') {
$where = 'WHERE player_id LIKE :search OR external_id LIKE :search OR country LIKE :search OR city LIKE :search OR device_type LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM onesignal_subscriptions $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalAll = (int) $db->query('SELECT COUNT(*) FROM onesignal_subscriptions')->fetchColumn();

$sql = "SELECT player_id, external_id, language, country, city, ip, device_type, device_model, device_os, sdk, last_active
        FROM onesignal_subscriptions
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

$data = array_map(static function (array $row): array {
    $platform = $row['device_type'] ?: ($row['device_os'] ?? null);
    return [
        'player_id' => $row['player_id'],
        'external_id' => $row['external_id'],
        'language' => $row['language'],
        'country' => $row['country'],
        'city' => $row['city'],
        'ip' => $row['ip'],
        'platform' => $platform,
        'device_model' => $row['device_model'],
        'device_os' => $row['device_os'],
        'sdk' => $row['sdk'],
        'last_active' => $row['last_active'],
    ];
}, $rows ?: []);

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $data,
]);
