<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../inc/pdf_bootstrap.php';

$trackingNumber = trim((string)($_GET['tracking_number'] ?? ''));
if ($trackingNumber === '') {
    http_response_code(400);
    exit('tracking_number required');
}

$stmt = db()->prepare('SELECT * FROM shipments WHERE tracking_number=:n LIMIT 1');
$stmt->execute(['n'=>$trackingNumber]);
$shipment = $stmt->fetch();
if (!$shipment) {
    http_response_code(404);
    exit('not found');
}
$eventsStmt = db()->prepare('SELECT status_code,status_note,city,country,created_at FROM shipment_events WHERE shipment_id=:id ORDER BY created_at DESC');
$eventsStmt->execute(['id'=>$shipment['id']]);
$events=$eventsStmt->fetchAll();
$lang = current_lang();
$cfg = settings();

$rows='';
foreach($events as $e){
    $rows .= '<tr><td>'.htmlspecialchars((string)$e['created_at']).'</td><td>'.htmlspecialchars(t('status',(string)$e['status_code'],$lang)).'</td><td>'.htmlspecialchars((string)($e['status_note']??'-')).'</td><td>'.htmlspecialchars(trim(($e['city']??'').'/'.($e['country']??''),'/')).'</td></tr>';
}
$html = '<html><meta charset="utf-8"><body style="font-family:DejaVu Sans,sans-serif;color:#111">'
    .'<div style="border-bottom:2px solid #0f172a;padding-bottom:10px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">'
    .'<div><h2 style="margin:0">'.htmlspecialchars((string)($cfg['company_name'] ?? 'CargoAfrik')).'</h2><div>'.htmlspecialchars((string)($cfg['company_email'] ?? '')).' | '.htmlspecialchars((string)($cfg['company_phone'] ?? '')).'</div></div>'
    .(!empty($cfg['logo_path']) ? '<img src="'.htmlspecialchars((string)$cfg['logo_path']).'" style="height:44px">' : '')
    .'</div>'
    .'<h3 style="margin:0 0 8px">'.htmlspecialchars(t('front','track',$lang)).' #'.htmlspecialchars($trackingNumber).'</h3>'
    .'<p><strong>'.htmlspecialchars(t('front','route',$lang)).':</strong> '.htmlspecialchars((string)$shipment['origin_country']).' / '.htmlspecialchars((string)$shipment['origin_city']).' → '.htmlspecialchars((string)$shipment['destination_country']).' / '.htmlspecialchars((string)$shipment['destination_city']).'</p>'
    .'<p><strong>'.htmlspecialchars(t('front','sender',$lang)).':</strong> '.htmlspecialchars((string)$shipment['sender_name']).' '.htmlspecialchars((string)$shipment['sender_company']).' - '.htmlspecialchars((string)$shipment['sender_phone']).'</p>'
    .'<p><strong>'.htmlspecialchars(t('front','receiver',$lang)).':</strong> '.htmlspecialchars((string)$shipment['receiver_name']).' - '.htmlspecialchars((string)$shipment['receiver_phone']).' / '.htmlspecialchars((string)$shipment['receiver_address']).'</p>'
    .'<p><strong>'.htmlspecialchars(t('front','description',$lang)).':</strong> '.htmlspecialchars((string)$shipment['description']).'</p>'
    .'<table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse"><thead><tr><th>Tarih</th><th>Durum</th><th>Not</th><th>Konum</th></tr></thead><tbody>'.$rows.'</tbody></table>'
    .'</body></html>';

$filename = preg_replace('/[^A-Za-z0-9\-_]/','',$trackingNumber) ?: 'tracking';
if (class_exists('\Mpdf\Mpdf')) {
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'tempDir' => __DIR__ . '/../tmp']);
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename.'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename.'.html');
echo $html;
