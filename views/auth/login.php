<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-light">
<main class="container py-5" style="max-width:520px">
    <h1 class="h3 mb-3">Oyuna Giriş</h1>
    <?php if (isset($_GET['error'])): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if (isset($_GET['toast'])): ?><div class="alert alert-success"><?= htmlspecialchars((string) $_GET['toast'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="/login" class="card card-body bg-black border-secondary">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label class="form-label">E-posta</label><input class="form-control mb-3" type="email" name="email" required>
        <label class="form-label">Şifre</label><input class="form-control mb-3" type="password" name="password" required>
        <button class="btn btn-primary">Giriş Yap</button>
        <a class="btn btn-link mt-2" href="/forgot-password">Şifremi unuttum</a>
        <a class="btn btn-link" href="/register">Hesabın yok mu? Kayıt ol</a>
    </form>
</main>
</body>
</html>
