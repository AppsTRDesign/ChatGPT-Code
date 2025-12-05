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

    $sql = "SELECT p.id, p.name, p.formatted_address, p.latitude, p.longitude, p.business_type, p.category_slug, p.city_name,
            COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
            COALESCE(p.rating,0) AS rating,
            COALESCE(p.user_ratings_total,0) + COALESCE(ur.user_review_count,0) AS user_ratings_total,
            (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews,
            CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
              THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))/(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
              ELSE COALESCE(p.rating,0) END AS combined_rating,
            p.business_image
            FROM places p
            LEFT JOIN (SELECT place_id, COUNT(*) AS visit_count FROM place_visits GROUP BY place_id) pv ON pv.place_id = p.id
            LEFT JOIN (SELECT place_id, COUNT(*) AS user_review_count, SUM(rating) AS user_review_sum FROM user_reviews GROUP BY place_id) ur ON ur.place_id = p.id";
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(name LIKE :q OR formatted_address LIKE :q)';
        $params[':q'] = "%{$q}%";
    }
    if ($category !== '') {
        $where[] = '(category_slug = :cat OR business_type = :cat)';
        $params[':cat'] = $category;
    }
    if ($city !== '') {
        $where[] = 'city_name = :city';
        $params[':city'] = $city;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT :limit';
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
