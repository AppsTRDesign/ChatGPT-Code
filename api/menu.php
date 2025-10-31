<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();

$categoryStatement = $db->prepare('SELECT id, name, icon, image FROM categories WHERE restaurant_id = :restaurant ORDER BY name');
$categoryStatement->execute([':restaurant' => $restaurantId]);
$categories = $categoryStatement->fetchAll() ?: [];

$productStatement = $db->prepare('SELECT id, category_id, name, description, price, image FROM products WHERE restaurant_id = :restaurant ORDER BY name');
$productStatement->execute([':restaurant' => $restaurantId]);
$products = array_map(static function ($product) {
    $product['price'] = (float)$product['price'];
    return $product;
}, $productStatement->fetchAll() ?: []);

Response::json([
    'categories' => $categories,
    'products' => $products,
]);
