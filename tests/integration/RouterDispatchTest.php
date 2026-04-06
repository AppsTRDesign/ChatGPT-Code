<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Router;

$router = new Router();
$router->get('/ping', static function (): void {
    echo 'pong';
});

ob_start();
$router->dispatch('GET', '/ping');
$output = ob_get_clean();
if ($output !== 'pong') {
    fwrite(STDERR, "Router dispatch failed\n");
    exit(1);
}

echo "Router integration test passed\n";
