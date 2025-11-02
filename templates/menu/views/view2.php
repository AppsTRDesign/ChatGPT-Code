<?php
$context['selectedView'] = $context['selectedView'] ?? 'view2';
$viewConfig = [
    'layoutClass' => 'layout-boutique',
    'heroVariant' => 'stacked',
    'navItems' => [
        ['target' => 'dailySection', 'icon' => 'bx bx-sun', 'label' => 'Günün'],
        ['target' => 'homeSection', 'icon' => 'bx bx-home-heart', 'label' => 'Ana'],
        ['target' => 'orderSection', 'icon' => 'bx bx-receipt', 'label' => 'Sipariş'],
        ['target' => 'contactSection', 'icon' => 'bx bx-message-dots', 'label' => 'İletişim'],
        ['target' => 'categorySection', 'icon' => 'bx bx-grid', 'label' => 'Menü'],
        ['target' => null, 'icon' => 'bx bx-cart', 'label' => 'Sepet', 'action' => 'cart'],
    ],
];
include __DIR__ . '/shared.php';
