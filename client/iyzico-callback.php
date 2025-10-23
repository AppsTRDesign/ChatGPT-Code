<?php
require_once __DIR__ . '/../config/config.php';

use App\Helpers;
use App\IyzicoService;
use App\Subscription;

$token = $_POST['token'] ?? '';
$status = 'error';
$message = 'Ödeme işlemi gerçekleştirilemedi.';

if ($token) {
    $result = IyzicoService::complete($token);
    if ($result) {
        $status = strtolower((string) ($result['status'] ?? 'error'));
        $conversationId = (int) ($result['conversationId'] ?? 0);
        if ($conversationId > 0) {
            IyzicoService::storeTransaction($conversationId, $token, $status, (string) ($result['raw'] ?? ''));
            if ($status === 'success' || $status === 'SUCCESS') {
                Subscription::activate($conversationId);
                $stmt = Helpers::db()->prepare('UPDATE user_packages SET status = "active" WHERE id = :id');
                $stmt->execute(['id' => $conversationId]);
                $message = 'Ödemeniz başarıyla alındı. Paketinizi hemen kullanmaya başlayabilirsiniz.';
            } else {
                $stmt = Helpers::db()->prepare('UPDATE user_packages SET status = "rejected" WHERE id = :id');
                $stmt->execute(['id' => $conversationId]);
                $message = 'Ödeme işlemi tamamlanamadı. Lütfen tekrar deneyin veya banka havalesi seçeneğini kullanın.';
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İyzico Ödeme Durumu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0b132b; color: #fff; font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: rgba(12,19,43,0.9); border-radius: 1.5rem; padding: 3rem; max-width: 520px; text-align: center; box-shadow: 0 24px 56px rgba(13,110,253,0.25); }
        .btn-primary { background: linear-gradient(120deg, #0d6efd, #4098ff); border: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="h4 mb-3">Ödeme Durumu</h1>
        <p class="mb-4"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars(rtrim(BASE_URL, '/') . '/client/purchase', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="btn btn-primary">Pakete Geri Dön</a>
    </div>
</body>
</html>
