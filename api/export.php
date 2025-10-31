<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\ReportExportService;

$type = $_GET['type'] ?? 'pdf';
$html = '<h1>Adisyon</h1><p>Örnek belge içeriği</p>';

$service = new ReportExportService();

if ($type === 'thermal') {
    $output = $service->renderThermal($html);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="adisyon-thermal.pdf"');
    echo $output;
    exit;
}

$output = $service->renderPdf($html);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="adisyon.pdf"');
echo $output;
exit;
