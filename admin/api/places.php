<?php
require_once __DIR__ . '/../auth.php';
admin_require_auth();
$pdo = admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $status = $_POST['status'] ?? '';
    $note = trim((string)($_POST['note'] ?? ''));
    if ($action === 'delete') {
        $stmt = $pdo->prepare('SELECT business_image, business_image_thumb FROM places WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $place = $stmt->fetch();
        if (!$place) {
            admin_json(['message' => 'Kayıt bulunamadı'], 404);
        }
        $pdo->beginTransaction();
        $reviewStmt = $pdo->prepare('SELECT review_photo_urls FROM user_reviews WHERE place_id = :pid');
        $reviewStmt->execute([':pid' => $id]);
        foreach ($reviewStmt as $reviewRow) {
            $photos = decode_json($reviewRow['review_photo_urls'] ?? '');
            admin_remove_files($photos);
        }
        $galleryStmt = $pdo->prepare('SELECT image_url, thumb_url FROM place_gallery_images WHERE place_id = :pid');
        $galleryStmt->execute([':pid' => $id]);
        foreach ($galleryStmt as $galleryRow) {
            admin_remove_file($galleryRow['image_url'] ?? '');
            admin_remove_file($galleryRow['thumb_url'] ?? '');
        }
        $serviceItemStmt = $pdo->prepare('SELECT image_url FROM place_service_items WHERE place_id = :pid');
        $serviceItemStmt->execute([':pid' => $id]);
        foreach ($serviceItemStmt as $serviceRow) {
            admin_remove_file($serviceRow['image_url'] ?? '');
        }
        admin_remove_file($place['business_image'] ?? '');
        admin_remove_file($place['business_image_thumb'] ?? '');

        $pdo->prepare('DELETE FROM user_reviews WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_hours WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_service_item_prices WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_service_items WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_service_categories WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_services WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_knows_about WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_visits WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_social_links WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_gallery_images WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_galleries WHERE place_id = :pid')->execute([':pid' => $id]);
        $pdo->prepare('DELETE FROM place_claim_requests WHERE place_id = :pid')->execute([':pid' => $id]);
        try {
            $pdo->prepare('DELETE FROM place_events WHERE place_id = :pid')->execute([':pid' => $id]);
        } catch (PDOException $e) {
            // ignore missing table
        }
        $pdo->prepare('DELETE FROM places WHERE id = :id')->execute([':id' => $id]);
        $pdo->commit();
        admin_json(['message' => 'İşletme silindi']);
    }
    if (!in_array($status, ['0', '1', '2'], true)) {
        admin_json(['message' => 'Geçersiz durum'], 400);
    }

    $stmt = $pdo->prepare('SELECT id FROM places WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetchColumn()) {
        admin_json(['message' => 'Kayıt bulunamadı'], 404);
    }

    if ($status === '2' && $note === '') {
        admin_json(['message' => 'Reddetme nedeni giriniz'], 400);
    }

    $pdo->prepare('UPDATE places SET status = :status, status_note = :note, updated_at = NOW() WHERE id = :id')
        ->execute([
            ':status' => $status,
            ':note' => $note,
            ':id' => $id,
        ]);

    admin_json(['message' => 'Durum güncellendi']);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE :q OR city_name LIKE :q OR business_type LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, ['0', '1', '2'], true)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM places {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$dataSql = "SELECT id, name, city_name, business_type, status, status_note, created_at, business_image, formatted_phone_number, description FROM places {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();

$items = [];
foreach ($dataStmt as $row) {
    $items[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'city' => $row['city_name'],
        'category' => $row['business_type'],
        'status' => (string)$row['status'],
        'status_note' => $row['status_note'] ?? '',
        'created_at' => $row['created_at'],
        'phone' => $row['formatted_phone_number'] ?? '',
        'description' => $row['description'] ?? '',
        'business_image' => $row['business_image'] ?? '',
    ];
}

$pages = max(1, (int)ceil($total / $perPage));
admin_json([
    'items' => $items,
    'meta' => [
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'per_page' => $perPage,
    ],
]);
