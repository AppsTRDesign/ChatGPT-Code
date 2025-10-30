<?php
class Order extends BaseModel
{
    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO orders (restaurant_id, table_id, table_number, order_number, customer_note, items, total_amount, currency, status, payment_status, payment_method, locale) VALUES (:restaurant_id, :table_id, :table_number, :order_number, :customer_note, :items, :total_amount, :currency, :status, :payment_status, :payment_method, :locale)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'table_id' => $data['table_id'] ?? null,
            'table_number' => $data['table_number'],
            'order_number' => $data['order_number'],
            'customer_note' => $data['customer_note'] ?? '',
            'items' => json_encode($data['items']),
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? 'TRY',
            'status' => $data['status'] ?? 'pending',
            'payment_status' => $data['payment_status'] ?? 'unpaid',
            'payment_method' => $data['payment_method'] ?? 'cash',
            'locale' => $data['locale'] ?? 'tr',
        ]);
        $orderId = (int)$this->db->lastInsertId();
        $this->logStatus($orderId, $data['status'] ?? 'pending', 'Sipariş oluşturuldu');
        return $orderId;
    }

    public function allByRestaurant(int $restaurantId, array $filters = [])
    {
        $query = 'SELECT o.*, t.name AS table_name, t.qr_token AS table_token FROM orders o LEFT JOIN restaurant_tables t ON t.id = o.table_id WHERE o.restaurant_id = :restaurant_id';
        $params = ['restaurant_id' => $restaurantId];
        if (!empty($filters['status'])) {
            $query .= ' AND o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['from'])) {
            $query .= ' AND o.created_at >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $query .= ' AND o.created_at <= :to';
            $params['to'] = $filters['to'];
        }
        $query .= ' ORDER BY o.created_at DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'], true) ?: [];
        }
        return $orders;
    }

    public function findByNumber(int $restaurantId, string $orderNumber)
    {
        $stmt = $this->db->prepare('SELECT o.*, t.qr_token FROM orders o LEFT JOIN restaurant_tables t ON t.id = o.table_id WHERE o.restaurant_id = :restaurant_id AND o.order_number = :order_number LIMIT 1');
        $stmt->execute(['restaurant_id' => $restaurantId, 'order_number' => $orderNumber]);
        $order = $stmt->fetch();
        if ($order) {
            $order['items'] = json_decode($order['items'], true) ?: [];
        }
        return $order;
    }

    public function updateStatus(int $orderId, string $status, ?string $note = null)
    {
        $stmt = $this->db->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
        $result = $stmt->execute(['status' => $status, 'id' => $orderId]);
        if ($result) {
            $this->logStatus($orderId, $status, $note);
        }
        return $result;
    }

    public function markPaid(int $orderId, string $method = 'cash')
    {
        $stmt = $this->db->prepare('UPDATE orders SET payment_status = "paid", payment_method = :method, paid_at = NOW(), updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['method' => $method, 'id' => $orderId]);
    }

    public function logStatus(int $orderId, string $status, ?string $note = null)
    {
        $stmt = $this->db->prepare('INSERT INTO order_status_logs (order_id, status, note) VALUES (:order_id, :status, :note)');
        return $stmt->execute([
            'order_id' => $orderId,
            'status' => $status,
            'note' => $note,
        ]);
    }

    public function timeline(int $orderId)
    {
        $stmt = $this->db->prepare('SELECT * FROM order_status_logs WHERE order_id = :order_id ORDER BY created_at ASC');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public function report(int $restaurantId, string $from, string $to)
    {
        $stmt = $this->db->prepare('SELECT DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as total, SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) AS completed_total FROM orders WHERE restaurant_id = :restaurant_id AND created_at BETWEEN :from AND :to GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC');
        $stmt->execute([
            'restaurant_id' => $restaurantId,
            'from' => $from,
            'to' => $to
        ]);
        return $stmt->fetchAll();
    }

    public function aggregateByStatus(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT status, COUNT(*) as total FROM orders WHERE restaurant_id = :restaurant_id GROUP BY status');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        $rows = $stmt->fetchAll();
        $result = ['pending' => 0, 'preparing' => 0, 'ready' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    public function openByTable(int $restaurantId): array
    {
        $stmt = $this->db->prepare('SELECT o.*, t.name AS table_name, t.qr_token AS table_token, t.id AS table_id FROM orders o INNER JOIN restaurant_tables t ON t.id = o.table_id WHERE o.restaurant_id = :restaurant_id AND o.payment_status = "unpaid" AND o.status IN ("pending","preparing","ready","completed") ORDER BY o.created_at DESC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $order['items'] = json_decode($order['items'], true) ?: [];
        }
        return $orders;
    }
}
