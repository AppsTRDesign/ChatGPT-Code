<?php
require_once __DIR__ . '/helpers.php';

class OrderService
{
    public function createOrder(int $tableId, array $items, ?string $note = null): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders (table_id, note, status, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
            $stmt->execute([$tableId, $note, 'pending']);
            $orderId = (int)$pdo->lastInsertId();

            $total = 0;
            $productIds = array_column($items, 'id');
            if ($productIds) {
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $productStmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
                $productStmt->execute($productIds);
                $productPrices = [];
                foreach ($productStmt->fetchAll() as $product) {
                    $productPrices[$product['id']] = (float)$product['price'];
                }
            } else {
                $productPrices = [];
            }

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            foreach ($items as $item) {
                $productId = (int)$item['id'];
                $quantity = max(1, (int)$item['quantity']);
                $price = $productPrices[$productId] ?? (float)($item['price'] ?? 0);
                $itemStmt->execute([$orderId, $productId, $quantity, $price]);
                $total += $price * $quantity;
            }

            $pdo->prepare('UPDATE orders SET total = ?, updated_at = NOW() WHERE id = ?')->execute([$total, $orderId]);
            $pdo->commit();

            return $this->getOrder($orderId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getOrder(int $orderId): ?array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT o.*, t.name AS table_name FROM orders o JOIN tables t ON t.id = o.table_id WHERE o.id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }
        $items = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
        $items->execute([$orderId]);
        $order['items'] = $items->fetchAll();
        return $order;
    }

    public function getOrderStatusForTable(int $tableId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, status, total, note, created_at, updated_at FROM orders WHERE table_id = ? ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$tableId]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $order['id'] = (int)$order['id'];
            $order['total'] = (float)$order['total'];
        }
        return $orders;
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $orderId]);
    }

    public function addOrderHistory(int $orderId, string $status, ?string $note = null): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id, status, note, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$orderId, $status, $note]);
    }

    public function getOrders(array $filters = []): array
    {
        $pdo = db();
        $query = 'SELECT o.id, o.table_id, o.total, o.status, o.note, o.created_at, o.updated_at, t.name AS table_name FROM orders o JOIN tables t ON t.id = o.table_id';
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if ($where) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= ' ORDER BY o.created_at DESC LIMIT 100';
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $order['id'] = (int)$order['id'];
            $order['total'] = (float)$order['total'];
        }
        if ($orders) {
            $ids = array_column($orders, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $itemStmt = $pdo->prepare("SELECT oi.order_id, oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($placeholders)");
            $itemStmt->execute($ids);
            $itemsByOrder = [];
            foreach ($itemStmt->fetchAll() as $item) {
                $itemsByOrder[$item['order_id']][] = [
                    'name' => $item['name'],
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price'],
                ];
            }
            foreach ($orders as &$order) {
                $order['items'] = $itemsByOrder[$order['id']] ?? [];
            }
        }
        return $orders;
    }

    public function getWaiterCalls(): array
    {
        $pdo = db();
        $stmt = $pdo->query('SELECT wc.id, wc.status, wc.created_at, wc.updated_at, t.name AS table_name FROM waiter_calls wc JOIN tables t ON t.id = wc.table_id WHERE wc.created_at >= NOW() - INTERVAL 3 DAY ORDER BY wc.created_at DESC');
        $calls = $stmt->fetchAll();
        foreach ($calls as &$call) {
            $call['id'] = (int)$call['id'];
        }
        return $calls;
    }

    public function acknowledgeWaiterCall(int $callId): void
    {
        $stmt = db()->prepare('UPDATE waiter_calls SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute(['completed', $callId]);
    }
}
