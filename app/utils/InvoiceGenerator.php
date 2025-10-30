<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use Mpdf\Mpdf;

class InvoiceGenerator
{
    public static function customerReceipt(array $restaurant, array $order): array
    {
        $items = self::orderItems($order);
        $meta = [
            'title' => 'Müşteri Adisyonu',
            'subtitle' => $restaurant['name'] ?? 'Restoran',
            'logo' => $restaurant['logo_url'] ?? null,
            'address' => $restaurant['address'] ?? '',
            'phone' => $restaurant['phone'] ?? '',
            'table' => $order['table_number'] ?? '-',
            'order_number' => $order['order_number'] ?? '',
            'status' => $order['status'] ?? '',
            'note' => $order['customer_note'] ?? '',
        ];
        $currency = $order['currency'] ?? ($restaurant['currency'] ?? 'TRY');
        $totals = [
            'subtotal' => $order['total_amount'] ?? 0,
            'total' => $order['total_amount'] ?? 0,
            'currency' => $currency,
        ];
        $html = self::buildHtml($meta, $items, $totals, $currency);
        $filename = 'siparis-' . ($order['order_number'] ?? uniqid()) . '.pdf';
        return self::toPdf($html, $filename);
    }

    public static function cashReceipt(array $restaurant, array $order): array
    {
        $items = self::orderItems($order);
        $meta = [
            'title' => 'Yazar Kasa Fişi',
            'subtitle' => $restaurant['name'] ?? 'Restoran',
            'logo' => $restaurant['logo_url'] ?? null,
            'address' => $restaurant['address'] ?? '',
            'phone' => $restaurant['phone'] ?? '',
            'table' => $order['table_number'] ?? '-',
            'order_number' => $order['order_number'] ?? '',
            'status' => $order['status'] ?? '',
            'note' => $order['customer_note'] ?? '',
        ];
        $currency = $order['currency'] ?? ($restaurant['currency'] ?? 'TRY');
        $totals = [
            'subtotal' => $order['total_amount'] ?? 0,
            'total' => $order['total_amount'] ?? 0,
            'currency' => $currency,
        ];
        $html = self::buildHtml($meta, $items, $totals, $currency, true);
        $filename = 'adisyon-' . ($order['order_number'] ?? uniqid()) . '.pdf';
        return self::toPdf($html, $filename, [
            'format' => [80, 200],
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 4,
            'margin_bottom' => 6,
        ]);
    }

    public static function ordersReport(array $orders, array $restaurant, array $range): array
    {
        $currency = $restaurant['currency'] ?? 'TRY';
        $items = array_map(function ($order) use ($currency) {
            $amount = $order['total_amount'] ?? 0;
            return [
                'description' => ($order['order_number'] ?? '#') . ' / ' . ($order['table_number'] ?? '-'),
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
                'currency' => $order['currency'] ?? $currency,
                'metadata' => [
                    'Durum' => $order['status'] ?? '-',
                    'Tarih' => $order['created_at'] ?? '',
                ],
            ];
        }, $orders);
        $totalAmount = array_reduce($items, fn($carry, $item) => $carry + (float)$item['total'], 0.0);
        $meta = [
            'title' => 'Sipariş Raporu',
            'subtitle' => $restaurant['name'] ?? 'Restoran',
            'logo' => $restaurant['logo_url'] ?? null,
            'address' => $restaurant['address'] ?? '',
            'phone' => $restaurant['phone'] ?? '',
            'period' => ($range['from'] ?? '') . ' - ' . ($range['to'] ?? ''),
        ];
        $totals = [
            'subtotal' => $totalAmount,
            'total' => $totalAmount,
            'currency' => $currency,
        ];
        $html = self::buildHtml($meta, $items, $totals, $currency);
        $filename = 'rapor-' . date('YmdHis') . '.pdf';
        return self::toPdf($html, $filename);
    }

    private static function orderItems(array $order): array
    {
        $items = json_decode($order['items'] ?? '[]', true);
        if (!is_array($items)) {
            $items = $order['items'] ?? [];
        }
        $items = is_array($items) ? $items : [];
        $currency = $order['currency'] ?? 'TRY';
        return array_map(function ($item) use ($currency) {
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['price'] ?? 0);
            return [
                'description' => $item['name'] ?? '-',
                'quantity' => $qty,
                'unit_price' => $price,
                'total' => $qty * $price,
                'currency' => $item['currency'] ?? $currency,
            ];
        }, $items);
    }

    private static function buildHtml(array $meta, array $items, array $totals, string $currency, bool $compact = false): string
    {
        $template = self::loadVendorTemplate();
        $lineItemsHtml = self::renderLineItems($items, $currency);
        $totalsHtml = self::renderTotals($totals);
        $replacements = [
            '{{company_name}}' => self::escape($meta['subtitle'] ?? ''),
            '{{company_logo}}' => self::escape($meta['logo'] ?? ''),
            '{{invoice_title}}' => self::escape($meta['title'] ?? ''),
            '{{invoice_subtitle}}' => self::escape($meta['period'] ?? ''),
            '{{invoice_meta}}' => self::renderMeta($meta),
            '{{line_items}}' => $lineItemsHtml,
            '{{totals}}' => $totalsHtml,
        ];
        if ($template) {
            $html = strtr($template, $replacements);
            if (!str_contains($html, '<style')) {
                $html = self::injectStyles($html, $compact);
            }
            return $html;
        }
        return self::fallbackTemplate($meta, $lineItemsHtml, $totalsHtml, $currency, $compact);
    }

    private static function renderLineItems(array $items, string $currency): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>';
            $rows .= '<td>' . self::escape($item['description'] ?? '-') . '</td>';
            $rows .= '<td class="qty">' . self::escape(number_format((float)($item['quantity'] ?? 0), 2, ',', '.')) . '</td>';
            $rows .= '<td class="price">' . self::escape(self::formatAmount($item['unit_price'] ?? 0)) . ' ' . self::escape($item['currency'] ?? $currency) . '</td>';
            $rows .= '<td class="total">' . self::escape(self::formatAmount($item['total'] ?? 0)) . ' ' . self::escape($item['currency'] ?? $currency) . '</td>';
            $rows .= '</tr>';
            if (!empty($item['metadata']) && is_array($item['metadata'])) {
                $rows .= '<tr class="item-meta"><td colspan="4">';
                foreach ($item['metadata'] as $label => $value) {
                    $rows .= '<span>' . self::escape($label) . ': ' . self::escape($value) . '</span> ';
                }
                $rows .= '</td></tr>';
            }
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="empty">Kayıt bulunamadı</td></tr>';
        }
        return $rows;
    }

    private static function renderTotals(array $totals): string
    {
        $html = '';
        if (isset($totals['subtotal'])) {
            $html .= '<tr><td>Ara Toplam</td><td>' . self::escape(self::formatAmount($totals['subtotal'])) . ' ' . self::escape($totals['currency'] ?? '') . '</td></tr>';
        }
        if (isset($totals['tax'])) {
            $html .= '<tr><td>Vergi</td><td>' . self::escape(self::formatAmount($totals['tax'])) . ' ' . self::escape($totals['currency'] ?? '') . '</td></tr>';
        }
        $html .= '<tr class="grand-total"><td>Genel Toplam</td><td>' . self::escape(self::formatAmount($totals['total'] ?? 0)) . ' ' . self::escape($totals['currency'] ?? '') . '</td></tr>';
        return $html;
    }

    private static function renderMeta(array $meta): string
    {
        $parts = [];
        if (!empty($meta['table'])) {
            $parts[] = '<span>Masa: ' . self::escape($meta['table']) . '</span>';
        }
        if (!empty($meta['order_number'])) {
            $parts[] = '<span>Sipariş #: ' . self::escape($meta['order_number']) . '</span>';
        }
        if (!empty($meta['status'])) {
            $parts[] = '<span>Durum: ' . self::escape($meta['status']) . '</span>';
        }
        if (!empty($meta['period'])) {
            $parts[] = '<span>Dönem: ' . self::escape($meta['period']) . '</span>';
        }
        if (!empty($meta['note'])) {
            $parts[] = '<span>Not: ' . self::escape($meta['note']) . '</span>';
        }
        return implode('', $parts);
    }

    private static function fallbackTemplate(array $meta, string $lineItemsHtml, string $totalsHtml, string $currency, bool $compact): string
    {
        $styles = self::vendorStyles();
        $styles .= 'body{font-family:\'DejaVu Sans\',sans-serif;color:#1f2937;margin:0;padding:24px;background:#f8fafc;}';
        $styles .= '.invoice{background:#fff;border-radius:12px;padding:24px;box-shadow:0 15px 35px rgba(15,23,42,0.08);}';
        $styles .= '.invoice-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}';
        $styles .= '.invoice-header .info{display:flex;flex-direction:column;}';
        $styles .= '.invoice-header img{max-height:64px;border-radius:8px;}';
        $styles .= '.invoice-meta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;color:#475569;}';
        $styles .= 'table{width:100%;border-collapse:collapse;margin-bottom:18px;}';
        $styles .= 'th,td{padding:10px 12px;border-bottom:1px solid #e2e8f0;text-align:left;}';
        $styles .= 'th{background:#f1f5f9;font-weight:600;color:#0f172a;}';
        $styles .= '.qty,.price,.total{text-align:right;}';
        $styles .= '.totals{width:280px;margin-left:auto;}';
        $styles .= '.totals td{padding:8px 0;}';
        $styles .= '.grand-total td{font-size:18px;font-weight:700;color:#0f172a;}';
        $styles .= '.note{margin-top:16px;color:#475569;font-size:13px;}';
        $styles .= '.item-meta span{display:inline-block;margin-right:12px;font-size:12px;color:#64748b;}';
        if ($compact) {
            $styles .= 'body{padding:12px;} .invoice{padding:12px;} th,td{padding:6px;} .grand-total td{font-size:14px;}';
        }
        $logo = '';
        if (!empty($meta['logo'])) {
            $logo = '<img src="' . self::escape($meta['logo']) . '" alt="Logo">';
        }
        $address = '';
        if (!empty($meta['address'])) {
            $address .= '<div>' . self::escape($meta['address']) . '</div>';
        }
        if (!empty($meta['phone'])) {
            $address .= '<div>' . self::escape($meta['phone']) . '</div>';
        }
        $metaInfo = self::renderMeta($meta);
        return '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><style>' . $styles . '</style></head><body>' .
            '<div class="invoice">'
            . '<div class="invoice-header">'
            . '<div class="info"><h1>' . self::escape($meta['title'] ?? '') . '</h1><h2 style="font-weight:500;font-size:16px;color:#64748b;">' . self::escape($meta['subtitle'] ?? '') . '</h2>' . $address . '</div>'
            . $logo
            . '</div>'
            . '<div class="invoice-meta">' . $metaInfo . '</div>'
            . '<table><thead><tr><th>Ürün</th><th class="qty">Adet</th><th class="price">Birim</th><th class="total">Toplam</th></tr></thead><tbody>' . $lineItemsHtml . '</tbody></table>'
            . '<table class="totals">' . $totalsHtml . '</table>'
            . '<div class="note">Fiyatlar ' . self::escape($currency) . ' cinsindendir. NoaSoft QR Menü sistemi tarafından oluşturuldu.</div>'
            . '</div></body></html>';
    }

    private static function loadVendorTemplate(): ?string
    {
        $base = __DIR__ . '/../../vendor/anvilco/html-pdf-invoice-template';
        if (!is_dir($base)) {
            return null;
        }
        $candidates = array_merge(glob($base . '/*.html') ?: [], glob($base . '/templates/*.html') ?: []);
        foreach ($candidates as $candidate) {
            $contents = @file_get_contents($candidate);
            if ($contents) {
                return $contents;
            }
        }
        return null;
    }

    private static function vendorStyles(): string
    {
        $base = __DIR__ . '/../../vendor/anvilco/html-pdf-invoice-template';
        if (!is_dir($base)) {
            return '';
        }
        $styles = '';
        foreach (['style.css', 'styles.css', 'main.css'] as $file) {
            $path = $base . '/' . $file;
            if (is_readable($path)) {
                $styles .= @file_get_contents($path) ?: '';
            }
        }
        return $styles;
    }

    private static function injectStyles(string $html, bool $compact): string
    {
        $styles = self::vendorStyles();
        if ($styles === '') {
            return $html;
        }
        $custom = '.item-meta span{display:inline-block;margin-right:12px;font-size:12px;color:#64748b;}';
        if ($compact) {
            $custom .= 'table tr td{padding:6px;}';
        }
        $styleBlock = '<style>' . $styles . $custom . '</style>';
        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $styleBlock . '</head>', $html);
        }
        return '<head>' . $styleBlock . '</head>' . $html;
    }

    private static function toPdf(string $html, string $filename, array $options = []): array
    {
        $format = $options['format'] ?? 'A4';
        $marginLeft = $options['margin_left'] ?? 10;
        $marginRight = $options['margin_right'] ?? 10;
        $marginTop = $options['margin_top'] ?? 16;
        $marginBottom = $options['margin_bottom'] ?? 16;
        if (class_exists(Mpdf::class)) {
            try {
                $config = [
                    'mode' => 'utf-8',
                    'format' => $format,
                    'tempDir' => sys_get_temp_dir(),
                    'margin_left' => $marginLeft,
                    'margin_right' => $marginRight,
                    'margin_top' => $marginTop,
                    'margin_bottom' => $marginBottom,
                    'default_font' => 'dejavusans',
                ];
                if (function_exists('mb_internal_encoding')) {
                    mb_internal_encoding('UTF-8');
                }
                $mpdf = new Mpdf($config);
                $mpdf->autoScriptToLang = true;
                $mpdf->autoLangToFont = true;
                $mpdf->SetDefaultFont('dejavusans');
                $mpdf->WriteHTML(mb_convert_encoding($html, 'UTF-8', 'UTF-8'));
                $pdf = $mpdf->Output('', 'S');
                return [
                    'filename' => $filename,
                    'content' => base64_encode($pdf),
                    'mime' => 'application/pdf',
                ];
            } catch (\Throwable $exception) {
                self::reportRendererFailure('mpdf', $exception);
            }
        }

        if (class_exists(Dompdf::class)) {
            try {
                $options = new Options();
                $options->set('defaultFont', 'DejaVu Sans');
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                $dompdf->setPaper($format);
                $dompdf->render();
                $pdf = $dompdf->output();
                return [
                    'filename' => $filename,
                    'content' => base64_encode($pdf),
                    'mime' => 'application/pdf',
                ];
            } catch (\Throwable $exception) {
                self::reportRendererFailure('dompdf', $exception);
            }
        }

        return [
            'filename' => $filename,
            'content' => base64_encode($html),
            'mime' => 'text/html',
        ];
    }

    private static function reportRendererFailure(string $driver, \Throwable $exception): void
    {
        if (class_exists('Symfony\\Component\\VarDumper\\VarDumper')) {
            \Symfony\Component\VarDumper\VarDumper::dump([
                'driver' => $driver,
                'message' => $exception->getMessage(),
            ]);
        } elseif (ini_get('display_errors')) {
            error_log(sprintf('[InvoiceGenerator] %s renderer failed: %s', $driver, $exception->getMessage()));
        }
    }

    private static function escape($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private static function formatAmount($value): string
    {
        return number_format((float)$value, 2, ',', '.');
    }
}
