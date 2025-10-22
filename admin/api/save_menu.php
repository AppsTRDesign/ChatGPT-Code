<?php
require_once __DIR__ . '/../../lib/helpers.php';
require_auth();

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$categories = $payload['categories'] ?? [];
$branding = $payload['branding'] ?? [];

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('TRUNCATE TABLE categories');
    $pdo->exec('TRUNCATE TABLE products');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $categoryStmt = $pdo->prepare('INSERT INTO categories (name, description, sort_order) VALUES (?, ?, ?)');
    $productStmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price, image_path, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');

    foreach ($categories as $index => $category) {
        $categoryStmt->execute([
            $category['name'] ?? 'Kategori',
            $category['description'] ?? null,
            $index,
        ]);
        $categoryId = (int)$pdo->lastInsertId();
        foreach ($category['products'] ?? [] as $pIndex => $product) {
            $productStmt->execute([
                $categoryId,
                $product['name'] ?? 'Ürün',
                $product['description'] ?? null,
                $product['price'] ?? 0,
                $product['image_path'] ?? null,
                !empty($product['is_active']) ? 1 : 0,
                $pIndex,
            ]);
        }
    }

    $pdo->exec('TRUNCATE TABLE settings');
    if ($branding) {
        $settingStmt = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)');
        foreach ($branding as $key => $value) {
            $settingStmt->execute([$key, $value]);
        }
    }

    $pdo->commit();
    cache_flush();
    json_response(['success' => true]);
} catch (\Throwable $e) {
    $pdo->rollBack();
    json_response(['error' => 'Menü kaydedilirken hata oluştu: ' . $e->getMessage()], 500);
}
