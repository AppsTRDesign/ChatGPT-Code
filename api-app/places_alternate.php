<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_pdo();

    /* ===============================
       PARAMS
    =============================== */
    $mode     = $_GET['mode'] ?? 'map'; // map | list | suggest
    $q        = trim((string)($_GET['q'] ?? ''));
    $category = trim((string)($_GET['category'] ?? ''));
    $city     = trim((string)($_GET['city'] ?? ''));
    $country  = trim((string)($_GET['country'] ?? ''));
    $town     = trim((string)($_GET['town'] ?? ''));

    $limit    = min(DEFAULT_MAP_LIMIT, max(1, (int)($_GET['limit'] ?? DEFAULT_MAP_LIMIT)));
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $perPage  = max(1, (int)($_GET['per_page'] ?? ($mode === 'list' ? HOME_SEARCH_LIMIT : $limit)));

    $sort     = $_GET['sort'] ?? 'new'; // new | rating_desc | rating_asc | views | reviews | distance
    $lat      = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $lng      = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
    $radiusKm = isset($_GET['radius_km']) ? (float)$_GET['radius_km'] : 0.0;

    $suggestLimit = min(20, max(1, (int)($_GET['a'] ?? 8)));

    /* ===============================
       BASE WHERE
    =============================== */
    $where  = ['p.status = 1'];
    $params = [];

    if ($category !== '') {
        $where[] = '(p.category_slug = :cat OR p.business_type = :cat)';
        $params[':cat'] = $category;
    }
    if ($city !== '') {
        $where[] = '(p.city_slug = :city OR p.city_name = :city)';
        $params[':city'] = $city;
    }
    if ($country !== '') {
        $where[] = '(UPPER(p.country_code) = :country_code OR p.country_slug = :country_slug OR p.country_name = :country_name)';
        $params[':country_code'] = strtoupper($country);
        $params[':country_slug'] = $country;
        $params[':country_name'] = $country;
    }
    if ($town !== '') {
        $where[] = 'p.town_slug = :town';
        $params[':town'] = $town;
    }

    /* ===============================
       FULLTEXT + FALLBACK LIKE
    =============================== */
    $useSearch = ($q !== '');

    if ($useSearch) {
        $params[':qft']  = $q;
        $params[':qlike'] = '%' . $q . '%';

        $where[] = "(
            MATCH(
                p.name,
                p.business_type,
                p.description,
                p.formatted_address,
                p.city_name,
                p.town_name,
                p.country_name
            ) AGAINST(:qft IN NATURAL LANGUAGE MODE)
            OR
            p.name LIKE :qlike
            OR
            p.business_type LIKE :qlike
        )";
    }

    /* ===============================
       DISTANCE
    =============================== */
    $distanceSelect = "NULL AS distance_m";
    $distanceWhere  = "";
    $distanceParams = [];

    $hasLatLng = is_finite($lat) && is_finite($lng);

    if ($hasLatLng) {
        $distanceSelect = "
            ST_Distance_Sphere(
                POINT(CAST(p.longitude AS DECIMAL(10,6)), CAST(p.latitude AS DECIMAL(10,6))),
                POINT(:lng, :lat)
            ) AS distance_m
        ";
        $distanceParams[':lat'] = $lat;
        $distanceParams[':lng'] = $lng;

        if ($radiusKm > 0) {
            $distanceWhere = " AND ST_Distance_Sphere(
                POINT(CAST(p.longitude AS DECIMAL(10,6)), CAST(p.latitude AS DECIMAL(10,6))),
                POINT(:lng, :lat)
            ) <= :radius_m";
            $distanceParams[':radius_m'] = $radiusKm * 1000;
        }
    }

    /* ===============================
       SORT
    =============================== */
    $orderBy = 'p.created_at DESC';
    switch ($sort) {
        case 'rating_desc': $orderBy = 'combined_rating DESC'; break;
        case 'rating_asc':  $orderBy = 'combined_rating ASC'; break;
        case 'views':       $orderBy = 'views DESC'; break;
        case 'reviews':     $orderBy = 'total_reviews DESC'; break;
        case 'distance':
            if ($hasLatLng) $orderBy = 'distance_m ASC';
            break;
    }

    /* ===============================
       SUGGEST (AUTOCOMPLETE)
    =============================== */
    if ($mode === 'suggest') {
        if ($q === '') {
            echo json_encode(['data'=>[]]);
            exit;
        }

        $params[':pre'] = $q . '%';
        $where[] = '(p.name LIKE :pre OR p.business_type LIKE :pre)';

        $sql = "
            SELECT p.id, p.name, p.business_type, p.formatted_address
            FROM places p
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.created_at DESC
            LIMIT :lim
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->bindValue(':lim',$suggestLimit,PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) $r['slug'] = build_place_slug($r);
        foreach ($rows as &$s) {
            $statusInfo = getPlaceCurrentStatusList((int)$s['id']);
            $statusClass = $statusInfo['status'] === 'open' ? 'text-success' : 'text-danger';
            $s["status_info"] = $statusInfo["text"];
            $s["status_class"] = $statusClass;
        }		

        echo json_encode(['data'=>$rows]);
        exit;
    }

    /* ===============================
       MAIN QUERY
    =============================== */
    $relevanceSelect = $useSearch
        ? "MATCH(
                p.name,
                p.business_type,
                p.description,
                p.formatted_address,
                p.city_name,
                p.town_name,
                p.country_name
           ) AGAINST(:qft IN NATURAL LANGUAGE MODE) AS relevance"
        : "0 AS relevance";

    $sql = "
        SELECT
            p.id, p.name, p.formatted_address, p.latitude, p.longitude,
            p.business_type, p.category_slug, p.city_slug, p.town_slug, p.country_slug,

            COALESCE(NULLIF(p.view_total,0), pv.visit_count, 0) AS views,
            COALESCE(p.rating,0) AS rating,
            (COALESCE(JSON_LENGTH(p.reviews),0)+COALESCE(ur.user_review_count,0)) AS total_reviews,

            CASE WHEN (COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))>0
              THEN (COALESCE(p.rating,0)*COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_sum,0))
                   /(COALESCE(p.user_ratings_total,0)+COALESCE(ur.user_review_count,0))
              ELSE COALESCE(p.rating,0) END AS combined_rating,

            {$relevanceSelect},
            {$distanceSelect}

        FROM places p
        LEFT JOIN (SELECT place_id,COUNT(*) visit_count FROM place_visits GROUP BY place_id) pv ON pv.place_id=p.id
        LEFT JOIN (SELECT place_id,COUNT(*) user_review_count,SUM(rating) user_review_sum FROM user_reviews GROUP BY place_id) ur ON ur.place_id=p.id
        WHERE " . implode(' AND ', $where) . $distanceWhere . "
        ORDER BY " . ($useSearch ? "relevance DESC, " : "") . $orderBy . "
        LIMIT :lim OFFSET :off
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    foreach ($distanceParams as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':lim', $mode==='list'?$perPage:$limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $mode==='list'?($page-1)*$perPage:0, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$r) $r['slug'] = build_place_slug($r);
    foreach ($data as &$s) {
        $statusInfo = getPlaceCurrentStatusList((int)$s['id']);
        $statusClass = $statusInfo['status'] === 'open' ? 'text-success' : 'text-danger';
        $s["status_info"] = $statusInfo["text"];
        $s["status_class"] = $statusClass;
    }	

    /* ===============================
       LIST TOTAL (FULLTEXT HARİÇ!)
    =============================== */
    if ($mode === 'list') {

        $countWhere  = ['p.status = 1'];
        $countParams = [];

        if ($category !== '') {
            $countWhere[] = '(p.category_slug = :cat OR p.business_type = :cat)';
            $countParams[':cat'] = $category;
        }
        if ($city !== '') {
            $countWhere[] = 'p.city_slug = :city';
            $countParams[':city'] = $city;
        }
        if ($country !== '') {
        $countWhere[] = '(UPPER(p.country_code) = :country_code OR p.country_slug = :country_slug OR p.country_name = :country_name)';
        $countParams[':country_code'] = strtoupper($country);
        $countParams[':country_slug'] = $country;
        $countParams[':country_name'] = $country;
        }
        if ($town !== '') {
            $countWhere[] = 'p.town_slug = :town';
            $countParams[':town'] = $town;
        }

        if ($q !== '') {
            $countParams[':qlike'] = '%' . $q . '%';
            $countWhere[] = "(
                p.name LIKE :qlike
                OR p.business_type LIKE :qlike
                OR p.description LIKE :qlike
                OR p.city_name LIKE :qlike
                OR p.town_name LIKE :qlike
                OR p.country_name LIKE :qlike
            )";
        }

        $countSql = "
            SELECT COUNT(*)
            FROM places p
            WHERE " . implode(' AND ', $countWhere);

        $countStmt = $pdo->prepare($countSql);
        foreach ($countParams as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();

        echo json_encode([
            'data'      => $data,
            'total'     => (int)$countStmt->fetchColumn(),
            'page'      => $page,
            'per_page'  => $perPage
        ]);
        exit;
    }

    echo json_encode(['data'=>$data]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'=>'server_error',
        'message'=>$e->getMessage()
    ]);
}
