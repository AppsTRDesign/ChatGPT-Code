<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\SettingsService;
use Core\Response;
use Helpers\Mail;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['error' => true, 'message' => 'Sadece POST istekleri desteklenir.'], 405);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim((string)($payload['name'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $message = trim((string)($payload['message'] ?? ''));

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Lütfen geçerli bir ad ve e-posta girin.');
    }

    if ($message === '') {
        throw new RuntimeException('Mesaj alanı boş bırakılamaz.');
    }

    $settingsService = new SettingsService(1);
    $settings = $settingsService->all();
    $mailSettings = $settings['mail'] ?? [];
    $restaurant = $settings['restaurant'] ?? [];

    $recipient = $mailSettings['notification_email']
        ?? ($mailSettings['from_email'] ?? '');
    if (!$recipient) {
        $recipient = $restaurant['email'] ?? '';
    }
    if (!$recipient) {
        $recipient = $restaurant['contact_email'] ?? '';
    }
    if (!$recipient) {
        throw new RuntimeException('İletişim e-postası yapılandırılmamış.');
    }

    $subject = 'Yeni İletişim Mesajı - ' . ($restaurant['name'] ?? 'QR Menü');
    $body = '<p>Merhaba,</p>'
        . '<p><strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong> kişisinden yeni bir iletişim mesajı aldınız.</p>'
        . '<p><strong>E-posta:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Mesaj:</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>'
        . '<p>QR Menü iletişim formu üzerinden iletilmiştir.</p>';

    $sent = Mail::send($recipient, $subject, $body, [
        'from_email' => $mailSettings['from_email'] ?? $recipient,
        'from_name' => $mailSettings['from_name'] ?? ($restaurant['name'] ?? 'QR Menü'),
        'reply_to' => $email,
        'reply_name' => $name,
    ]);

    if (!$sent) {
        throw new RuntimeException('Mesaj gönderilirken bir hata oluştu.');
    }

    Response::json([
        'message' => 'Mesajınız başarıyla gönderildi. En kısa sürede dönüş yapacağız.',
    ]);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}
