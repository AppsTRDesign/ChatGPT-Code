<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Giriş</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-login-page">
<main class="container py-5">
    <div class="card mx-auto admin-login-card">
        <div class="card-body">
            <h1 class="h4 mb-3">Admin Panel Girişi</h1>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">Giriş başarısız.</div>
            <?php endif; ?>
            <form method="post" action="/admin/login">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3"><label class="form-label">Kullanıcı Adı</label><input class="form-control" name="username" required></div>
                <div class="mb-3"><label class="form-label">Şifre</label><input class="form-control" type="password" name="password" required></div>
                <button class="btn btn-primary w-100" type="submit">Giriş Yap</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
