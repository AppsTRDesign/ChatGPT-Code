<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Yeni Şifre</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-light">
<main class="container py-5" style="max-width:520px">
    <h1 class="h4 mb-3">Yeni Şifre Belirle</h1>
    <?php if (isset($_GET['error'])): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="/reset-password" class="card card-body bg-black border-secondary">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <label class="form-label">Yeni Şifre (min 8)</label>
        <input class="form-control mb-3" type="password" name="password" minlength="8" required>
        <button class="btn btn-success">Şifreyi Güncelle</button>
        <a class="btn btn-link mt-2" href="/login">Girişe dön</a>
    </form>
</main>
</body>
</html>
