<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;
use App\Settings;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if (!Settings::onesignalEnabled() || !Helpers::tableExists('web_push_campaigns')) {
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
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['title', 'status', 'target_count', 'created_at', 'sent_at'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'created_at';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE title LIKE :search OR message LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM web_push_campaigns $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalAll = (int) $db->query('SELECT COUNT(*) FROM web_push_campaigns')->fetchColumn();

$sql = "SELECT id, onesignal_id, title, message, language, url, image_path, target_type, target_count, status, stats_json, created_by, created_at, sent_at
        FROM web_push_campaigns
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
    $stats = [];
    if (!empty($row['stats_json'])) {
        $decoded = json_decode($row['stats_json'], true);
        if (is_array($decoded)) {
            $stats = $decoded;
        }
    }
    return [
        'id' => (int) $row['id'],
        'onesignal_id' => $row['onesignal_id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'language' => $row['language'],
        'url' => $row['url'],
        'image_path' => $row['image_path'],
        'target_type' => $row['target_type'],
        'target_count' => (int) $row['target_count'],
        'status' => $row['status'],
        'stats' => $stats,
        'created_at' => $row['created_at'],
        'sent_at' => $row['sent_at'],
    ];
}, $rows ?: []);

echo json_encode([
    'total' => $total,
    'totalNotFiltered' => $totalAll,
    'rows' => $data,
]);
