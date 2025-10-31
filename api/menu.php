<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Response;

$categories = [
    [
        'id' => 1,
        'name' => 'Beverages',
        'icon' => 'coffee',
        'image' => '',
    ],
    [
        'id' => 2,
        'name' => 'Foods',
        'icon' => 'utensils',
        'image' => '',
    ],
];

$products = [
    [
        'id' => 1,
        'category_id' => 1,
        'name' => 'Creamy Ice Coffee',
        'description' => 'Soğuk kahve karışımı',
        'price' => 89.00,
        'image' => 'assets/vendor/demo/coffee-1.png',
    ],
    [
        'id' => 2,
        'category_id' => 2,
        'name' => 'Hot Chocolate Cake',
        'description' => 'Sıcak çikolatalı kek',
        'price' => 129.50,
        'image' => 'assets/vendor/demo/cake-1.png',
    ],
];

Response::json([
    'categories' => $categories,
    'products' => $products,
]);
