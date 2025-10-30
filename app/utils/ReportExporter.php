<?php
class ReportExporter
{
    public static function toCsv(array $orders, string $currency): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['Sipariş No', 'Masa', 'Durum', 'Toplam', 'Para Birimi', 'Oluşturulma']);
        foreach ($orders as $order) {
            fputcsv($fh, [
                $order['order_number'] ?? $order['id'],
                $order['table_number'] ?? '-',
                $order['status'] ?? '-',
                number_format((float)$order['total_amount'], 2, ',', '.'),
                $order['currency'] ?? $currency,
                $order['created_at'] ?? '',
            ]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }
}
