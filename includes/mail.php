<?php

function send_order_status_email(string $to, string $name, string $status, int $orderId): void
{
    $subject = "Sipariş Durum Güncellemesi #{$orderId}";
    $statusLabels = [
        'pending' => 'Bekleniyor',
        'approved' => 'Onaylandı',
        'preparing' => 'Hazırlanıyor',
        'shipping' => 'Yola Çıktı',
        'delivered' => 'Teslim Edildi',
    ];
    $statusText = $statusLabels[$status] ?? $status;

    $body = "<html><body style='font-family: Arial, sans-serif; background:#f9f5f6; padding:20px;'>";
    $body .= "<table style='max-width:600px;margin:0 auto;background:#fff;border-radius:16px;padding:24px;box-shadow:0 10px 20px rgba(0,0,0,0.08);'>";
    $body .= "<tr><td>Merhaba {$name},</td></tr>";
    $body .= "<tr><td style='padding-top:12px;'>Siparişinizin durumu güncellendi.</td></tr>";
    $body .= "<tr><td style='padding-top:12px;'><strong>Yeni Durum:</strong> {$statusText}</td></tr>";
    $body .= "<tr><td style='padding-top:12px;'>Sipariş Numaranız: #{$orderId}</td></tr>";
    $body .= "<tr><td style='padding-top:24px;'>Bizi tercih ettiğiniz için teşekkür ederiz.</td></tr>";
    $body .= "</table></body></html>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: no-reply@noasoft.org\r\n";

    @mail($to, $subject, $body, $headers);
}
