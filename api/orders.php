<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();
$currency = $db->query("SELECT currency FROM restaurants WHERE id = {$restaurantId}")->fetchColumn() ?: 'TRY';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $orderId = (int)($payload['id'] ?? 0);
        $status = $payload['status'] ?? '';
        if ($orderId <= 0 || $status === '') {
            throw new InvalidArgumentException('Sipariş güncellenemedi.');
        }

        $statement = $db->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$status, $restaurantId, $orderId]);

        $order = fetchOrder($db, $orderId, $currency);

        Response::json([
            'success' => true,
            'order' => $order,
            'message' => 'Sipariş durumu güncellendi.',
        ]);
        return;
    }

    $statusFilter = $_GET['status'] ?? 'all';
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT o.id FROM orders o INNER JOIN tables t ON t.id = o.table_id WHERE o.restaurant_id = :restaurant";
    $params = [':restaurant' => $restaurantId];

    if ($statusFilter !== 'all') {
        $sql .= ' AND o.status = :status';
        $params[':status'] = $statusFilter;
    }

    if ($search !== '') {
        $sql .= ' AND (t.name LIKE :search OR o.id LIKE :searchExact)';
        $params[':search'] = "%{$search}%";
        $params[':searchExact'] = "%{$search}%";
    }

    $sql .= ' ORDER BY o.created_at DESC LIMIT 200';

    $statement = $db->prepare($sql);
    $statement->execute($params);
    $orders = [];
    foreach ($statement->fetchAll() ?: [] as $orderRow) {
        $order = fetchOrder($db, (int)$orderRow['id'], $currency);
        if (!empty($order)) {
            $orders[] = $order;
        }
    }

    Response::json([
        'orders' => $orders,
    ]);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}

function fetchOrder(\PDO $db, int $orderId, string $currency): array
{
    $statement = $db->prepare("SELECT o.id, o.table_id, t.name AS table_name, o.status, o.total, DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM orders o
        INNER JOIN tables t ON t.id = o.table_id
        WHERE o.id = ?");
    $statement->execute([$orderId]);
    $order = $statement->fetch();
    if (!$order) {
        return [];
    }

    $itemsStatement = $db->prepare('SELECT oi.id, p.name, oi.quantity, oi.unit_price, pv.name AS variant_name FROM order_items oi INNER JOIN products p ON p.id = oi.product_id LEFT JOIN product_variants pv ON pv.id = oi.variant_id WHERE oi.order_id = ?');
    $itemsStatement->execute([$orderId]);
    $items = array_map(static function ($item) use ($currency) {
        $item['unit_price'] = (float)$item['unit_price'];
        $item['total'] = $item['unit_price'] * (int)$item['quantity'];
        $item['total_formatted'] = number_format($item['total'], 2, ',', '.') . ' ' . $currency;
        $item['unit_price_formatted'] = number_format($item['unit_price'], 2, ',', '.') . ' ' . $currency;
        return $item;
    }, $itemsStatement->fetchAll() ?: []);

    $order['items'] = $items;
    $order['table'] = $order['table_name'];
    $order['total'] = (float)$order['total'];
    $order['total_formatted'] = number_format($order['total'], 2, ',', '.') . ' ' . $currency;
    unset($order['table_name']);

    return $order;
}
