<?php

declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

if (!verify_csrf($_POST['csrf'] ?? null)) {
    json_response(false, 'Güvenlik doğrulaması başarısız');
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Geçerli bir e-posta girin');
}

try {
    $stmt = db()->prepare('INSERT INTO subscribers(email,created_at) VALUES(:email,NOW()) ON DUPLICATE KEY UPDATE email=email');
    $stmt->execute(['email' => $email]);
    json_response(true, t('front', 'newsletter_success'));
} catch (Throwable $e) {
    json_response(false, 'Abonelik kaydedilemedi');
}
