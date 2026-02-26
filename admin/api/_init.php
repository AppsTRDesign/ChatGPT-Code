<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf'] ?? null)) {
    json_response(false, 'Geçersiz istek');
}
