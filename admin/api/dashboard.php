<?php
require_once __DIR__ . '/../../lib/helpers.php';
require_auth();

$pdo = db();

$stats = [
    'total_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','preparing')")->fetchColumn(),
    'active_tables' => (int)$pdo->query("SELECT COUNT(*) FROM tables WHERE status IN ('occupied','reserved','serving')")->fetchColumn(),
    'waiter_calls' => (int)$pdo->query("SELECT COUNT(*) FROM waiter_calls WHERE status = 'pending'")->fetchColumn(),
];

$recentOrders = $pdo->query('SELECT o.id, o.total, o.status, o.created_at, t.name AS table_name FROM orders o JOIN tables t ON t.id = o.table_id ORDER BY o.created_at DESC LIMIT 5')->fetchAll();
foreach ($recentOrders as &$order) {
    $order['id'] = (int)$order['id'];
    $order['total'] = (float)$order['total'];
}

json_response(['stats' => $stats, 'recent_orders' => $recentOrders]);
