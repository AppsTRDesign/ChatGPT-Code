<?php
require_once __DIR__ . '/../../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('client');
Helpers::requireAjax();
header('Content-Type: application/json; charset=utf-8');

$user = Auth::user();
$userId = (int) $user['id'];

$db = Helpers::db();
$limit = max(1, min(50, (int) ($_GET['limit'] ?? 10)));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');

$allowedSort = ['created_at', 'type'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'created_at';
}
if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

$where = 'WHERE user_id = :user_id';
$params = ['user_id' => $userId];

if ($search !== '') {
    $where .= ' AND (type LIKE :search OR content LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM qr_codes {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "SELECT id, type, origin, content, files_json, created_at FROM qr_codes {$where} ORDER BY {$sort} {$order} LIMIT :limit OFFSET :offset";
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
    $downloads = [];
    $previewData = null;
    foreach ($files as $file) {
        $format = $file['format'] ?? 'png';
        $downloads[] = [
            'format' => $format,
            'label' => strtoupper($format),
            'url' => '/client/qr-download.php?id=' . $row['id'] . '&format=' . $format,
            'size' => $file['size'] ?? null,
        ];
        if ($previewData === null && !empty($file['filename'])) {
            $absolute = dirname(__DIR__, 2) . '/uploads/qr/' . $userId . '/' . $file['filename'];
            if (is_file($absolute) && filesize($absolute) <= 3145728) {
                $mime = $file['mime'] ?? ($format === 'jpg' ? 'image/jpeg' : ($format === 'svg' ? 'image/svg+xml' : 'image/png'));
                $previewData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolute));
            }
        }
    }

    $mapped[] = [
        'id' => (int) $row['id'],
        'type' => $row['type'],
        'type_label' => $typeLabels[strtolower($row['type'])] ?? ucfirst($row['type']),
        'origin' => $row['origin'],
        'origin_label' => $originLabels[$row['origin']] ?? ucfirst($row['origin']),
        'created_at' => $row['created_at'],
        'downloads' => $downloads,
        'preview' => $previewData,
        'content_excerpt' => mb_strimwidth($row['content'] ?? '', 0, 120, '…'),
    ];
}

echo json_encode([
    'total' => $total,
    'rows' => $mapped,
]);
