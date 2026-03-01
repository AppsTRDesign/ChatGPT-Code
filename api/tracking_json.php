<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(false, 'Invalid request');
}

$number = trim((string)($_GET['tracking_number'] ?? ''));
if ($number === '') {
    json_response(false, 'tracking_number gerekli');
}

$available = supported_langs();
$langRequested = strtolower(trim((string)($_GET['lang'] ?? '')));
$lang = $langRequested !== '' ? $langRequested : detect_browser_lang($available);
if (!in_array($lang, $available, true)) {
    $lang = DEFAULT_LANG;
}

$stmt = db()->prepare(<<<SQL
SELECT
  s.*,
  COALESCE(oc_req.name, oc_en.name, oc.name, s.origin_country) AS origin_country_name,
  COALESCE(dc_req.name, dc_en.name, dc.name, s.destination_country) AS destination_country_name
FROM shipments s
LEFT JOIN countries oc ON oc.id = s.origin_country_id
LEFT JOIN country_translations oc_req ON oc_req.country_id = oc.id AND oc_req.lang_code = :lang
LEFT JOIN country_translations oc_en ON oc_en.country_id = oc.id AND oc_en.lang_code = :default_lang
LEFT JOIN countries dc ON dc.id = s.destination_country_id
LEFT JOIN country_translations dc_req ON dc_req.country_id = dc.id AND dc_req.lang_code = :lang2
LEFT JOIN country_translations dc_en ON dc_en.country_id = dc.id AND dc_en.lang_code = :default_lang2
WHERE s.tracking_number = :num
LIMIT 1
SQL
);
$stmt->execute([
    'num' => $number,
    'lang' => $lang,
    'lang2' => $lang,
    'default_lang' => DEFAULT_LANG,
    'default_lang2' => DEFAULT_LANG,
]);
$shipment = $stmt->fetch();

if (!$shipment) {
    json_response(false, t('front', 'tracking_not_found', $lang));
}

$eventsStmt = db()->prepare(<<<SQL
SELECT
  se.id,
  se.status_code,
  se.status_note,
  se.city,
  COALESCE(ct_req.name, ct_en.name, se.country) AS country,
  se.latitude,
  se.longitude,
  se.created_at
FROM shipment_events se
LEFT JOIN country_translations ct_req ON ct_req.country_id = se.country_id AND ct_req.lang_code = :lang
LEFT JOIN country_translations ct_en ON ct_en.country_id = se.country_id AND ct_en.lang_code = :default_lang
WHERE se.shipment_id = :id
ORDER BY se.created_at DESC, se.id DESC
SQL
);
$eventsStmt->execute([
    'id' => $shipment['id'],
    'lang' => $lang,
    'default_lang' => DEFAULT_LANG,
]);
$events = $eventsStmt->fetchAll();

foreach ($events as &$ev) {
    $ev['status_label'] = t('status', (string)$ev['status_code'], $lang);
}
unset($ev);

$shipment['origin_country'] = $shipment['origin_country_name'];
$shipment['destination_country'] = $shipment['destination_country_name'];
$shipment['status_label'] = t('status', (string)$shipment['current_status'], $lang);

json_response(true, t('front', 'tracking_found', $lang), [
    'meta' => [
        'tracking_number' => $number,
        'lang_requested' => $langRequested !== '' ? $langRequested : null,
        'lang_used' => $lang,
        'fallback_default_lang' => DEFAULT_LANG,
    ],
    'shipment' => $shipment,
    'events' => $events,
]);
