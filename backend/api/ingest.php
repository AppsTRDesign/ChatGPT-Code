<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    json_response(['error' => 'invalid_json'], 400);
}

$token = $input['token'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');
require_token($token);

$payload = $input['payload'] ?? null;
if (!$payload || !isset($payload['results']) || !is_array($payload['results'])) {
    json_response(['error' => 'missing_payload'], 400);
}

$source = $payload['source'] ?? 'unknown';
$cityName = $payload['city'] ?? ($payload['city_name'] ?? '');
$results = $payload['results'];

try {
    $db = Database::instance();
    $db->beginTransaction();

    $stmt = $db->prepare(
        'INSERT INTO places (
            source, city_name, name, formatted_address, latitude, longitude,
            rating, user_ratings_total, formatted_phone_number, telephone_type,
            website, business_type, opening_hours, busy_hours,
            business_image, reviews
        ) VALUES (
            :source, :city_name, :name, :formatted_address, :latitude, :longitude,
            :rating, :user_ratings_total, :formatted_phone_number, :telephone_type,
            :website, :business_type, :opening_hours, :busy_hours,
            :business_image, :reviews
        )'
    );

    $existsStmt = $db->prepare(
        'SELECT id FROM places WHERE (
            (latitude = :latitude AND longitude = :longitude AND latitude <> "" AND longitude <> "")
            OR (formatted_address = :formatted_address AND formatted_address <> "")
        ) LIMIT 1'
    );

    $inserted = 0;

    foreach ($results as $row) {
        $rowCity = $cityName;
        if (!$rowCity && isset($row['city_name'])) {
            $rowCity = $row['city_name'];
        }
        $openingHours = [];
        if (isset($row['opening_hours']) && is_array($row['opening_hours'])) {
            $openingHours = $row['opening_hours'];
        }

        $busyHours = [];
        if (isset($row['busy_hours']) && is_array($row['busy_hours'])) {
            $busyHours = $row['busy_hours'];
        }

        $reviews = [];
        if (isset($row['reviews']) && is_array($row['reviews'])) {
            $reviews = $row['reviews'];
        }

        $existsStmt->execute([
            ':latitude' => $row['latitude'] ?? '',
            ':longitude' => $row['longitude'] ?? '',
            ':formatted_address' => $row['formatted_address'] ?? '',
        ]);

        if ($existsStmt->fetchColumn()) {
            continue;
        }

        $stmt->execute([
            ':source' => $source,
            ':city_name' => $rowCity,
            ':name' => $row['name'] ?? '',
            ':formatted_address' => $row['formatted_address'] ?? '',
            ':latitude' => $row['latitude'] ?? '',
            ':longitude' => $row['longitude'] ?? '',
            ':rating' => $row['rating'] ?? null,
            ':user_ratings_total' => $row['user_ratings_total'] ?? null,
            ':formatted_phone_number' => $row['formatted_phone_number'] ?? '',
            ':telephone_type' => $row['telephone_type'] ?? '',
            ':website' => $row['website'] ?? '',
            ':business_type' => $row['business_type'] ?? '',
            ':opening_hours' => json_encode($openingHours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':busy_hours' => json_encode($busyHours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':business_image' => $row['business_image'] ?? '',
            ':reviews' => json_encode($reviews, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $inserted++;
    }

    $db->commit();
} catch (Throwable $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    json_response(['error' => 'db_error', 'detail' => $e->getMessage()], 500);
}

json_response(['status' => 'ok', 'inserted' => $inserted]);
