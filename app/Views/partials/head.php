<?php
use App\Services\Container;
$baseUrl = rtrim(Container::config('base_url'), '/');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'WebPush Platformu') ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <script>
        window.APP_BASE_URL = <?= json_encode($baseUrl) ?>;
    </script>
    <script defer src="<?= asset('assets/js/app.js') ?>"></script>
</head>
<body>
