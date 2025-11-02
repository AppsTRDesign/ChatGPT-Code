<?php
/** @var array $context */
$context = $context ?? [];
$selectedView = $context['selectedView'] ?? 'view1';
$viewFile = __DIR__ . '/views/' . $selectedView . '.php';

if (!is_file($viewFile)) {
    $selectedView = 'view1';
    $context['selectedView'] = $selectedView;
    $viewFile = __DIR__ . '/views/view1.php';
}

include $viewFile;
