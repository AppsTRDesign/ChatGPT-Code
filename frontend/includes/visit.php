<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$placeId = (int)($data['place_id'] ?? 0);
if ($placeId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'invalid_place']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$visitorHash = hash('sha256', $ip . '|' . $ua);
$today = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d');

try {
    $pdo = get_pdo();
    $pdo->beginTransaction();

    // Ensure place exists
    $exists = $pdo->prepare('SELECT id FROM places WHERE id = :id LIMIT 1');
    $exists->execute([':id' => $placeId]);
    if (!$exists->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    $check = $pdo->prepare('SELECT id FROM place_visits WHERE place_id = :pid AND visitor_hash = :vh AND visit_date = :vd LIMIT 1');
    $check->execute([':pid' => $placeId, ':vh' => $visitorHash, ':vd' => $today]);
    $already = (bool)$check->fetchColumn();

    if (!$already) {
        $insert = $pdo->prepare('INSERT INTO place_visits (place_id, visitor_hash, visit_date) VALUES (:pid, :vh, :vd)');
        $insert->execute([':pid' => $placeId, ':vh' => $visitorHash, ':vd' => $today]);
        $pdo->prepare('UPDATE places SET view_total = view_total + 1 WHERE id = :id')->execute([':id' => $placeId]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'counted' => $already ? 0 : 1]);
} catch (Throwable $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
