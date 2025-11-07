<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\ReportExportService;
use App\Services\SettingsService;
use Core\Database;
use Core\Response;

$restaurantId = 1;
$format = $_GET['format'] ?? ($_GET['type'] ?? 'pdf');
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$startParam = $_GET['start'] ?? null;
$endParam = $_GET['end'] ?? null;

$startDate = $startParam ? new DateTimeImmutable($startParam . ' 00:00:00') : new DateTimeImmutable('-6 days 00:00:00');
$endDate = $endParam ? new DateTimeImmutable($endParam . ' 23:59:59') : new DateTimeImmutable('now');

$db = Database::connection();
$settingsService = new SettingsService($restaurantId);
$settings = $settingsService->all();
$currency = $settings['restaurant']['currency'] ?? 'TRY';
$restaurantName = $settings['restaurant']['name'] ?? 'Restoran';

$service = new ReportExportService();

if ($orderId > 0) {
    exportOrder($db, $service, $restaurantId, $orderId, $currency, $restaurantName, $format);
}

$statement = $db->prepare("SELECT DATE(created_at) AS period,
        COUNT(*) AS orders,
        SUM(CASE WHEN status IN ('Ödeme Alındı', 'Tamamlandı') THEN total ELSE 0 END) AS completed_revenue,
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

function exportOrder(\PDO $db, ReportExportService $service, int $restaurantId, int $orderId, string $currency, string $restaurantName, string $format): void
{
    $order = fetchOrder($db, $restaurantId, $orderId, $currency);
    if (!$order) {
        Response::json(['error' => true, 'message' => 'Sipariş bulunamadı.'], 404);
    }

    $html = renderOrderHtml($order, $restaurantName);
    if ($format === 'thermal' || $format === 'yazar') {
        $output = $service->renderThermal($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="yazarkasa-' . $orderId . '.pdf"');
        echo $output;
        exit;
    }

    $output = $service->renderPdf($html);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="adisyon-' . $orderId . '.pdf"');
    echo $output;
    exit;
}

function fetchOrder(\PDO $db, int $restaurantId, int $orderId, string $currency): array
{
    $statement = $db->prepare("SELECT o.id, o.status, o.total, DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') AS created_at, t.name AS table_name
        FROM orders o
        INNER JOIN tables t ON t.id = o.table_id
        WHERE o.restaurant_id = ? AND o.id = ?");
    $statement->execute([$restaurantId, $orderId]);
    $order = $statement->fetch();
    if (!$order) {
        return [];
    }
    $order['total'] = (float)$order['total'];
    $order['total_formatted'] = number_format($order['total'], 2, ',', '.') . ' ' . $currency;

    $itemsStatement = $db->prepare('SELECT p.name, oi.quantity, pv.name AS variant_name, oi.unit_price FROM order_items oi INNER JOIN products p ON p.id = oi.product_id LEFT JOIN product_variants pv ON pv.id = oi.variant_id WHERE oi.order_id = ?');
    $itemsStatement->execute([$orderId]);
    $order['items'] = array_map(static function ($item) use ($currency) {
        $item['unit_price'] = (float)$item['unit_price'];
        $item['unit_price_formatted'] = number_format($item['unit_price'], 2, ',', '.') . ' ' . $currency;
        $item['line_total'] = $item['unit_price'] * (int)$item['quantity'];
        $item['line_total_formatted'] = number_format($item['line_total'], 2, ',', '.') . ' ' . $currency;
        return $item;
    }, $itemsStatement->fetchAll() ?: []);

    return $order;
}

function renderOrderHtml(array $order, string $restaurantName): string
{
    $itemsRows = '';
    foreach ($order['items'] as $item) {
        $variant = $item['variant_name'] ? ' (' . $item['variant_name'] . ')' : '';
        $itemsRows .= '<tr>'
            . '<td>' . htmlspecialchars($item['name'] . $variant, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>'
            . '<td style="text-align:center">' . (int)$item['quantity'] . '</td>'
            . '<td style="text-align:right">' . $item['unit_price_formatted'] . '</td>'
            . '<td style="text-align:right">' . $item['line_total_formatted'] . '</td>'
            . '</tr>';
    }

    if ($itemsRows === '') {
        $itemsRows = '<tr><td colspan="4">Kalem bulunamadı.</td></tr>';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.4; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 8px; }
        p { margin: 0 0 6px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #f3f4f6; font-weight: 600; }
        tfoot td { font-weight: 600; font-size: 12px; }
    </style>
    <title>Adisyon #{$order['id']}</title>
</head>
<body>
    <h1>{$restaurantName} - Adisyon</h1>
    <p>Sipariş No: #{$order['id']} • Masa: {$order['table_name']} • Tarih: {$order['created_at']}</p>
    <table>
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Adet</th>
                <th>Birim Fiyat</th>
                <th>Tutar</th>
            </tr>
        </thead>
        <tbody>
            {$itemsRows}
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Toplam</td>
                <td style="text-align:right">{$order['total_formatted']}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
HTML;
}
