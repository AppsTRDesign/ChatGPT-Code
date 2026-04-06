<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Noa Political Wars', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/css/jsvectormap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?= $content ?? '' ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.APP_CONFIG = {
    csrf: <?= json_encode($csrf ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    toastMessage: <?= json_encode($_GET['toast'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    apiBase: '/api/v1',
    socketUrl: <?= json_encode($config['socket_url'] ?? 'http://127.0.0.1:3001', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
};
</script>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
