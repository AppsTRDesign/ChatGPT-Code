<?php
require_once __DIR__ . '/../../lib/OrderService.php';
require_once __DIR__ . '/../../lib/InvoicePdf.php';
require_auth();

$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) {
    http_response_code(400);
    echo 'Sipariş bulunamadı.';
    exit;
}

$service = new OrderService();
$order = $service->getOrder($orderId);
if (!$order) {
    http_response_code(404);
    echo 'Sipariş bulunamadı.';
    exit;
}

try {
    [$fontPath, $fontName] = ensure_invoice_font();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Yazı tipi indirilemedi. Lütfen yöneticinizle iletişime geçin.';
    exit;
}

$pdf = new InvoicePdf($fontPath, $fontName);
$pdf->addPage();

$pdf->writeLine('Noasoft QR Menü Adisyonu', 18);
$pdf->writeLine('Sipariş #: ' . $order['id']);
$pdf->writeLine('Masa: ' . $order['table_name']);
$pdf->writeLine('Durum: ' . strtoupper($order['status']));
$pdf->writeLine('Oluşturulma: ' . $order['created_at']);
if (!empty($order['note'])) {
    $pdf->writeLine('Not: ' . $order['note']);
}
$pdf->writeLine(' ');

$rows = [];
foreach ($order['items'] as $item) {
    $rows[] = [
        'product' => $item['name'],
        'qty' => $item['quantity'],
        'price' => number_format($item['price'], 2) . ' ₺',
        'total' => number_format($item['quantity'] * $item['price'], 2) . ' ₺',
    ];
}
$pdf->drawTable($rows, [
    ['key' => 'product', 'title' => 'Ürün', 'width' => 100],
    ['key' => 'qty', 'title' => 'Adet', 'width' => 20],
    ['key' => 'price', 'title' => 'Birim', 'width' => 30],
    ['key' => 'total', 'title' => 'Toplam', 'width' => 30],
], 90);

$pdf->writeLine(' ');
$pdf->writeLine('Genel Toplam: ' . number_format($order['total'], 2) . ' ₺', 14);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="adisyon-' . $order['id'] . '.pdf"');
echo $pdf->output();
