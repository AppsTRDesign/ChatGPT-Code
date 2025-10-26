<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz güvenlik belirteci.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

try {
    if (strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($message) < 10) {
        throw new RuntimeException('Lütfen tüm alanları doğru doldurun.');
    }
    $stmt = $pdo->prepare('INSERT INTO contacts (name, email, message) VALUES (:name, :email, :message)');
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':message' => $message,
    ]);
    echo json_encode(['status' => 'success', 'message' => 'Mesajınız alındı. En kısa sürede dönüş yapılacaktır.']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
