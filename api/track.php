<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Invalid request');
if (!verify_csrf($_POST['csrf'] ?? null)) json_response(false, 'CSRF mismatch');
if (strcasecmp($_SESSION['captcha_text'] ?? '', trim($_POST['captcha'] ?? '')) !== 0) json_response(false, t('front', 'captcha_invalid'));

$number = trim((string)($_POST['tracking_number'] ?? ''));
$lang = current_lang();
$stmt = db()->prepare('SELECT s.*, COALESCE(oc_t.name, oc.name, s.origin_country) AS origin_country_name, COALESCE(dc_t.name, dc.name, s.destination_country) AS destination_country_name FROM shipments s LEFT JOIN countries oc ON oc.id = s.origin_country_id LEFT JOIN country_translations oc_t ON oc_t.country_id = oc.id AND oc_t.lang_code = :lang LEFT JOIN countries dc ON dc.id = s.destination_country_id LEFT JOIN country_translations dc_t ON dc_t.country_id = dc.id AND dc_t.lang_code = :lang2 WHERE s.tracking_number = :num LIMIT 1');
$stmt->execute(['num' => $number, 'lang'=>$lang, 'lang2'=>$lang]);
$shipment = $stmt->fetch();
if (!$shipment) json_response(false, t('front', 'tracking_not_found'));

$eventsStmt = db()->prepare('SELECT status_code, status_note, city, COALESCE(ct.name, se.country) AS country, latitude, longitude, created_at FROM shipment_events se LEFT JOIN country_translations ct ON ct.country_id = se.country_id AND ct.lang_code = :lang WHERE shipment_id = :id ORDER BY created_at DESC');
$eventsStmt->execute(['id' => $shipment['id'], 'lang'=>$lang]);
$events = $eventsStmt->fetchAll();

foreach ($events as &$ev) {
    $ev['status_label'] = t('status', (string)$ev['status_code']);
}
unset($ev);

$shipment['origin_country'] = $shipment['origin_country_name'];
$shipment['destination_country'] = $shipment['destination_country_name'];
$shipment['status_label'] = t('status', (string)$shipment['current_status']);
json_response(true, t('front', 'tracking_found'), ['shipment' => $shipment, 'events' => $events]);
