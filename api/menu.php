<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\MenuService;
use Core\Response;

$languageParam = isset($_GET['lang']) ? strtolower((string)$_GET['lang']) : null;
$language = null;
if ($languageParam !== null) {
    $sanitized = preg_replace('/[^a-z]/', '', $languageParam);
    $language = $sanitized !== '' ? $sanitized : null;
}

$service = new MenuService(1, $language);

Response::json([
    'categories' => $service->categories(),
    'products' => $service->products(),
    'daily_menu' => $service->dailyMenu(),
]);
