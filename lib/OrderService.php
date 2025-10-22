<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/MenuService.php';

class OrderService
{
    private DataStore $orderStore;
    private DataStore $waiterStore;
    private MenuService $menuService;

    public function __construct()
    {
        $this->orderStore = datastore('orders', [
            'orders' => []
        ]);
        $this->waiterStore = datastore('waiter_calls', [
            'calls' => []
        ]);
        $this->menuService = new MenuService();
    }

    public function createOrder(string $tableToken, array $items): array
    {
        $table = $this->menuService->findTableByToken($tableToken);
        if (!$table) {
            throw new RuntimeException('Masa bulunamadı');
        }

        $order = [
            'id' => uniqid('ord_'),
            'table_token' => $tableToken,
            'table_name' => $table['name'],
            'items' => $items,
            'status' => 'pending',
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $this->orderStore->update(function ($data) use ($order) {
            $data['orders'][] = $order;
            return $data;
        });

        return $order;
    }

    public function getOrders(?string $tableToken = null): array
    {
        $orders = $this->orderStore->all()['orders'] ?? [];
        if ($tableToken) {
            $orders = array_values(array_filter($orders, fn($order) => $order['table_token'] === $tableToken));
        }
        usort($orders, fn($a, $b) => $b['created_at'] <=> $a['created_at']);
        return $orders;
    }

    public function updateStatus(string $orderId, string $status): ?array
    {
        $orders = $this->orderStore->all()['orders'] ?? [];
        foreach ($orders as &$order) {
            if ($order['id'] === $orderId) {
                $order['status'] = $status;
                $order['updated_at'] = time();
                $this->orderStore->save(['orders' => $orders]);
                return $order;
            }
        }
        return null;
    }

    public function getStats(): array
    {
        $orders = $this->getOrders();
        $totalOrders = count($orders);
        $pending = count(array_filter($orders, fn($order) => $order['status'] === 'pending'));
        $preparing = count(array_filter($orders, fn($order) => $order['status'] === 'preparing'));
        $completed = count(array_filter($orders, fn($order) => $order['status'] === 'completed'));

        return [
            'total_orders' => $totalOrders,
            'pending' => $pending,
            'preparing' => $preparing,
            'completed' => $completed,
        ];
    }

    public function callWaiter(string $tableToken): array
    {
        $table = $this->menuService->findTableByToken($tableToken);
        if (!$table) {
            throw new RuntimeException('Masa bulunamadı');
        }

        $call = [
            'id' => uniqid('call_'),
            'table_token' => $tableToken,
            'table_name' => $table['name'],
            'created_at' => time(),
            'handled' => false,
        ];

        $this->waiterStore->update(function ($data) use ($call) {
            $data['calls'][] = $call;
            return $data;
        });

        return $call;
    }

    public function getWaiterCalls(): array
    {
        $calls = $this->waiterStore->all()['calls'] ?? [];
        usort($calls, fn($a, $b) => $b['created_at'] <=> $a['created_at']);
        return $calls;
    }

    public function markCallHandled(string $callId): ?array
    {
        $calls = $this->waiterStore->all()['calls'] ?? [];
        foreach ($calls as &$call) {
            if ($call['id'] === $callId) {
                $call['handled'] = true;
                $this->waiterStore->save(['calls' => $calls]);
                return $call;
            }
        }
        return null;
    }
}
