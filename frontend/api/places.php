<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_pdo();
    $q = trim($_GET['q'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $city = trim($_GET['city'] ?? '');
    $limit = min(DEFAULT_MAP_LIMIT, max(1, (int)($_GET['limit'] ?? DEFAULT_MAP_LIMIT)));

    $sql = "SELECT id, name, formatted_address, latitude, longitude, business_type, rating, user_ratings_total, business_image, city_name FROM places";
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(name LIKE :q OR formatted_address LIKE :q)';
        $params[':q'] = "%{$q}%";
    }
    if ($category !== '') {
        $where[] = 'business_type = :cat';
        $params[':cat'] = $category;
    }
    if ($city !== '') {
        $where[] = 'city_name = :city';
        $params[':city'] = $city;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();
    foreach ($data as &$row) {
        $row['slug'] = build_place_slug($row);
    }
    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
