<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$subject || !$message) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi girin.']);
    exit;
}

$body = "Ad: {$name}\nE-posta: {$email}\nKonu: {$subject}\n\nMesaj:\n{$message}";
$recipient = ns_config('mail.to', ns_config('site.noreply_email', 'noreply@localhost.localdomain'));
$fromHeader = ns_config('mail.from', ns_config('site.noreply_email', 'noreply@localhost.localdomain'));
$extraHeaders = ns_config('mail.headers', []);
if (!is_array($extraHeaders)) {
    $extraHeaders = $extraHeaders ? [$extraHeaders] : [];
}

$headers = array_merge([
    'From: ' . $fromHeader,
    'Reply-To: ' . $email,
], $extraHeaders);

$subjectPrefix = ns_config('site.name', 'NoaSoft Converter') . ' İletişim: ';

$sent = mail($recipient, $subjectPrefix . $subject, $body, implode("\r\n", $headers));

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Mesaj gönderilemedi. Lütfen daha sonra tekrar deneyin.']);
}
