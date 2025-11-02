<?php
$context['selectedView'] = $context['selectedView'] ?? 'view3';
$viewConfig = [
    'layoutClass' => 'layout-gallery',
    'heroVariant' => 'split',
    'navItems' => [
        ['target' => 'homeSection', 'icon' => 'bx bx-compass', 'label' => 'Keşfet'],
        ['target' => 'categorySection', 'icon' => 'bx bx-category', 'label' => 'Kategoriler'],
        ['target' => 'dailySection', 'icon' => 'bx bx-star', 'label' => 'Özel'],
        ['target' => 'orderSection', 'icon' => 'bx bx-time-five', 'label' => 'Takip'],
        ['target' => 'contactSection', 'icon' => 'bx bx-phone-call', 'label' => 'İletişim'],
        ['target' => null, 'icon' => 'bx bx-cart', 'label' => 'Sepet', 'action' => 'cart'],
    ],
];
include __DIR__ . '/shared.php';
