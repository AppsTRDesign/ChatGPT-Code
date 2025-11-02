<?php
$context['selectedView'] = $context['selectedView'] ?? 'view5';
$viewConfig = [
    'layoutClass' => 'layout-terra',
    'heroVariant' => 'split',
    'navItems' => [
        ['target' => 'homeSection', 'icon' => 'bx bx-leaf', 'label' => 'Terra'],
        ['target' => 'dailySection', 'icon' => 'bx bx-hot', 'label' => 'Sıcak'],
        ['target' => 'categorySection', 'icon' => 'bx bx-food-menu', 'label' => 'Menü'],
        ['target' => 'orderSection', 'icon' => 'bx bx-hourglass', 'label' => 'Durum'],
        ['target' => 'contactSection', 'icon' => 'bx bx-chat', 'label' => 'İletişim'],
        ['target' => null, 'icon' => 'bx bx-cart', 'label' => 'Sepet', 'action' => 'cart'],
    ],
];
include __DIR__ . '/shared.php';
