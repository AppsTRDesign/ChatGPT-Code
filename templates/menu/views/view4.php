<?php
$context['selectedView'] = $context['selectedView'] ?? 'view4';
$viewConfig = [
    'layoutClass' => 'layout-lounge',
    'heroVariant' => 'stacked',
    'navItems' => [
        ['target' => 'homeSection', 'icon' => 'bx bx-home-smile', 'label' => 'Lounge'],
        ['target' => 'dailySection', 'icon' => 'bx bx-crown', 'label' => 'Şef'],
        ['target' => 'categorySection', 'icon' => 'bx bx-menu', 'label' => 'Menü'],
        ['target' => 'orderSection', 'icon' => 'bx bx-bell', 'label' => 'Takip'],
        ['target' => 'contactSection', 'icon' => 'bx bx-support', 'label' => 'Destek'],
        ['target' => null, 'icon' => 'bx bx-cart', 'label' => 'Sepet', 'action' => 'cart'],
    ],
];
include __DIR__ . '/shared.php';
