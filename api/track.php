<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Invalid request');
if (!verify_csrf($_POST['csrf'] ?? null)) json_response(false, 'CSRF mismatch');
if (strcasecmp($_SESSION['captcha_text'] ?? '', trim($_POST['captcha'] ?? '')) !== 0) json_response(false, t('front', 'captcha_invalid'));

$number = trim((string)($_POST['tracking_number'] ?? ''));
$stmt = db()->prepare('SELECT * FROM shipments WHERE tracking_number = :num LIMIT 1');
$stmt->execute(['num' => $number]);
$shipment = $stmt->fetch();
if (!$shipment) json_response(false, t('front', 'tracking_not_found'));

$eventsStmt = db()->prepare('SELECT status_code, status_note, city, country, latitude, longitude, created_at FROM shipment_events WHERE shipment_id = :id ORDER BY created_at DESC');
$eventsStmt->execute(['id' => $shipment['id']]);
$events = $eventsStmt->fetchAll();

foreach ($events as &$ev) {
    $ev['status_label'] = t('status', (string)$ev['status_code']);
}
unset($ev);

$shipment['status_label'] = t('status', (string)$shipment['current_status']);
json_response(true, t('front', 'tracking_found'), ['shipment' => $shipment, 'events' => $events]);
