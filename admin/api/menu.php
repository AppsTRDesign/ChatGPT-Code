<?php
require_once __DIR__ . '/../../lib/helpers.php';
require_auth();

$pdo = db();

$categories = $pdo->query('SELECT id, name, description, sort_order FROM categories ORDER BY sort_order, name')->fetchAll();
$productStmt = $pdo->query('SELECT id, category_id, name, description, price, image_path, is_active, sort_order FROM products ORDER BY category_id, sort_order, name');
$products = $productStmt->fetchAll();

foreach ($categories as &$category) {
    $category['id'] = (int)$category['id'];
    $category['products'] = array_values(array_filter($products, fn($product) => (int)$product['category_id'] === $category['id']));
}

$settings = $pdo->query('SELECT `key`, `value` FROM settings')->fetchAll();
$branding = [];
foreach ($settings as $setting) {
    $branding[$setting['key']] = $setting['value'];
}

json_response([
    'categories' => $categories,
    'branding' => $branding,
]);
