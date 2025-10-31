<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['error' => true, 'message' => 'Geçersiz istek.'], 405);
    exit;
}

$restaurantId = 1;
$db = Database::connection();
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$tableId = (int)($payload['table_id'] ?? 0);
$items = $payload['items'] ?? [];

if ($tableId <= 0 || empty($items)) {
    Response::json(['error' => true, 'message' => 'Sipariş oluşturulamadı.'], 400);
    exit;
}

try {
    $db->beginTransaction();

    $total = 0;
    $preparedItems = [];

    foreach ($items as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
        $quantity = max(1, (int)($item['quantity'] ?? 1));

        $productStmt = $db->prepare('SELECT id, price FROM products WHERE id = ? AND restaurant_id = ?');
        $productStmt->execute([$productId, $restaurantId]);
        $product = $productStmt->fetch();
        if (!$product) {
            throw new InvalidArgumentException('Ürün bulunamadı.');
        }

        $unitPrice = (float)$product['price'];
        if ($variantId) {
            $variantStmt = $db->prepare('SELECT id, price FROM product_variants WHERE id = ? AND product_id = ?');
            $variantStmt->execute([$variantId, $productId]);
            $variant = $variantStmt->fetch();
            if ($variant) {
                $unitPrice = (float)$variant['price'];
            }
        }

        $lineTotal = $unitPrice * $quantity;
        $total += $lineTotal;

        $preparedItems[] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ];
    }

    $orderStmt = $db->prepare('INSERT INTO orders (restaurant_id, table_id, status, total) VALUES (?, ?, "Beklemede", ?)');
    $orderStmt->execute([$restaurantId, $tableId, $total]);
    $orderId = (int)$db->lastInsertId();

    $db->prepare('UPDATE tables SET status = "occupied", updated_at = NOW() WHERE id = ? AND restaurant_id = ?')->execute([$tableId, $restaurantId]);

    $itemStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)');
    foreach ($preparedItems as $preparedItem) {
        $itemStmt->execute([
            $orderId,
            $preparedItem['product_id'],
            $preparedItem['variant_id'],
            $preparedItem['quantity'],
            $preparedItem['unit_price'],
        ]);
    }

    $db->commit();

    Response::json([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Siparişiniz iletildi.',
    ]);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}
