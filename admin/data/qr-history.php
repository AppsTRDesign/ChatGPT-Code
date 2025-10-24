<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
Helpers::requireAjax();
header('Content-Type: application/json; charset=utf-8');

$db = Helpers::db();
$limit = max(1, min(50, (int) ($_GET['limit'] ?? 10)));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['created_at', 'type', 'origin'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'created_at';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (u.username LIKE :search OR u.email LIKE :search OR q.type LIKE :search OR q.content LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM qr_codes q JOIN users u ON u.id = q.user_id {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "SELECT q.id, q.user_id, q.type, q.origin, q.files_json, q.meta_json, q.content, q.created_at, u.username, u.email
        FROM qr_codes q
        JOIN users u ON u.id = q.user_id
        {$where}
        ORDER BY q.{$sort} {$order}
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$typeLabels = [
    'url' => 'URL',
    'text' => 'Metin',
    'email' => 'E-posta',
    'phone' => 'Telefon',
    'sms' => 'SMS',
    'wifi' => 'Wi-Fi',
    'location' => 'Konum',
    'event' => 'Etkinlik',
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'twitter' => 'Twitter',
    'youtube' => 'YouTube',
    'whatsapp' => 'WhatsApp',
    'bitcoin' => 'Bitcoin',
    'ethereum' => 'Ethereum',
    'custom' => 'Özel Veri',
];
$originLabels = [
    'api' => 'API',
    'client' => 'Panel',
];

$mapped = [];
foreach ($rows as $row) {
    $files = json_decode($row['files_json'] ?? '[]', true) ?: [];
    $meta = json_decode($row['meta_json'] ?? '[]', true) ?: [];
    $downloads = [];
    foreach ($files as $file) {
        $format = $file['format'] ?? 'png';
        $downloads[] = [
            'format' => $format,
            'label' => strtoupper($format),
            'url' => '/admin/qr-download.php?id=' . $row['id'] . '&format=' . $format,
            'size' => $file['size'] ?? null,
        ];
    }

    $metaParts = [];
    if (!empty($meta['request_method'])) {
        $metaParts[] = strtoupper($meta['request_method']);
    }
    if (!empty($meta['token_label'])) {
        $metaParts[] = 'Token: ' . $meta['token_label'];
    }
    if (!empty($meta['source'])) {
        $metaParts[] = ucfirst($meta['source']);
    }
    $metaLabel = $metaParts ? implode(' • ', $metaParts) : '-';

    $mapped[] = [
        'id' => (int) $row['id'],
        'user_id' => (int) $row['user_id'],
        'user' => $row['username'] . ' (' . $row['email'] . ')',
        'type' => $row['type'],
        'type_label' => $typeLabels[strtolower($row['type'])] ?? ucfirst($row['type']),
        'origin' => $row['origin'],
        'origin_label' => $originLabels[$row['origin']] ?? ucfirst($row['origin']),
        'meta_label' => $metaLabel,
        'created_at' => $row['created_at'],
        'downloads' => $downloads,
        'content_excerpt' => mb_strimwidth($row['content'] ?? '', 0, 120, '…'),
    ];
}

echo json_encode([
    'total' => $total,
    'rows' => $mapped,
]);
