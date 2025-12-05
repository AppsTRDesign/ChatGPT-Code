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

try {
    $db = Database::instance();
    $stmt = $db->prepare('INSERT INTO submissions (source, payload) VALUES (:source, :payload)');
    $stmt->execute([
        ':source' => $payload['source'] ?? 'unknown',
        ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
} catch (Throwable $e) {
    json_response(['error' => 'db_error', 'detail' => $e->getMessage()], 500);
}

json_response(['status' => 'ok']);
