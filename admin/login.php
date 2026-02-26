<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password_hash'])) {
        $_SESSION['admin_id'] = (int) $admin['id'];
        header('Location: /admin/index.php');
        exit;
    }

    $error = 'Giriş başarısız';
}
?>
<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/assets/css/style.css"><title>Admin Giriş</title></head>
<body><section class="container section"><h1>Admin Giriş</h1><?php if (!empty($error)): ?><p><?= htmlspecialchars($error, ENT_QUOTES) ?></p><?php endif; ?><form method="post" class="panel"><input name="email" type="email" required placeholder="admin@cargoafrik.org"><input name="password" type="password" required placeholder="Şifre"><button>Giriş Yap</button></form></section></body></html>
