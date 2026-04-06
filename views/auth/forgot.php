<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Şifre Sıfırla</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-light">
<main class="container py-5" style="max-width:520px">
    <h1 class="h4 mb-3">Şifre Sıfırlama</h1>
    <?php if (isset($_GET['error'])): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if (isset($_GET['toast'])): ?><div class="alert alert-success"><?= htmlspecialchars((string) $_GET['toast'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['debug_reset_token'])): ?><div class="alert alert-warning">DEV TOKEN: <?= htmlspecialchars((string) $_SESSION['debug_reset_token'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="/forgot-password" class="card card-body bg-black border-secondary">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label class="form-label">E-posta</label>
        <input class="form-control mb-3" type="email" name="email" required>
        <button class="btn btn-warning">Sıfırlama Linki Üret</button>
        <a class="btn btn-link mt-2" href="/login">Girişe dön</a>
    </form>
</main>
</body>
</html>
