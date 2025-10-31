<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\ReportExportService;
use Core\Database;
use App\Services\SettingsService;

$restaurantId = 1;
$format = $_GET['format'] ?? ($_GET['type'] ?? 'pdf');
$startParam = $_GET['start'] ?? null;
$endParam = $_GET['end'] ?? null;

$startDate = $startParam ? new DateTimeImmutable($startParam . ' 00:00:00') : new DateTimeImmutable('-6 days 00:00:00');
$endDate = $endParam ? new DateTimeImmutable($endParam . ' 23:59:59') : new DateTimeImmutable('now');

$db = Database::connection();
$settingsService = new SettingsService($restaurantId);
$settings = $settingsService->all();
$currency = $settings['restaurant']['currency'] ?? 'TRY';
$restaurantName = $settings['restaurant']['name'] ?? 'Restoran';

$statement = $db->prepare("SELECT DATE(created_at) AS period,
        COUNT(*) AS orders,
        SUM(CASE WHEN status = 'Tamamlandı' THEN total ELSE 0 END) AS completed_revenue,
        SUM(CASE WHEN status IN ('Beklemede','Hazırlanıyor') THEN total ELSE 0 END) AS pending_revenue
    FROM orders
    WHERE restaurant_id = :restaurant AND created_at BETWEEN :start AND :end
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC");
$statement->execute([
    ':restaurant' => $restaurantId,
    ':start' => $startDate->format('Y-m-d H:i:s'),
    ':end' => $endDate->format('Y-m-d H:i:s'),
]);

$rows = $statement->fetchAll() ?: [];

$htmlRows = '';
$totalOrders = 0;
$totalCompleted = 0;
$totalPending = 0;

foreach ($rows as $row) {
    $date = (new DateTimeImmutable($row['period']))->format('d.m.Y');
    $orders = (int)$row['orders'];
    $completed = (float)$row['completed_revenue'];
    $pending = (float)$row['pending_revenue'];
    $totalOrders += $orders;
    $totalCompleted += $completed;
    $totalPending += $pending;
    $htmlRows .= sprintf('<tr><td>%s</td><td>%d</td><td>%s %s</td><td>%s %s</td></tr>',
        $date,
        $orders,
        number_format($completed, 2, ',', '.'),
        $currency,
        number_format($pending, 2, ',', '.'),
        $currency
    );
}

if ($htmlRows === '') {
    $htmlRows = '<tr><td colspan="4">Veri bulunamadı.</td></tr>';
}

$totalCompletedFormatted = number_format($totalCompleted, 2, ',', '.') . ' ' . $currency;
$totalPendingFormatted = number_format($totalPending, 2, ',', '.') . ' ' . $currency;

$html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { text-align: center; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        tfoot td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>{$restaurantName} - Satış Raporu</h1>
    <p>Dönem: {$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')}</p>
    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Sipariş Adedi</th>
                <th>Tamamlanan Ciro</th>
                <th>Bekleyen Ciro</th>
            </tr>
        </thead>
        <tbody>
            {$htmlRows}
        </tbody>
        <tfoot>
            <tr>
                <td>Toplam</td>
                <td>{$totalOrders}</td>
                <td>{$totalCompletedFormatted}</td>
                <td>{$totalPendingFormatted}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
HTML;

$service = new ReportExportService();

if ($format === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rapor.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tarih', 'Sipariş Adedi', 'Tamamlanan Ciro', 'Bekleyen Ciro']);
    foreach ($rows as $row) {
        fputcsv($output, [
            (new DateTimeImmutable($row['period']))->format('d.m.Y'),
            $row['orders'],
            number_format($row['completed_revenue'], 2, ',', '.') . ' ' . $currency,
            number_format($row['pending_revenue'], 2, ',', '.') . ' ' . $currency,
        ]);
    }
    fclose($output);
    exit;
}

if ($format === 'thermal') {
    $output = $service->renderThermal($html);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="adisyon-thermal.pdf"');
    echo $output;
    exit;
}

$output = $service->renderPdf($html);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="rapor.pdf"');
echo $output;
exit;
