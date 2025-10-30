<?php
class Order extends BaseModel
{
    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO orders (restaurant_id, table_number, customer_note, items, total_amount, status, locale) VALUES (:restaurant_id, :table_number, :customer_note, :items, :total_amount, :status, :locale)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'table_number' => $data['table_number'],
            'customer_note' => $data['customer_note'] ?? '',
            'items' => json_encode($data['items']),
            'total_amount' => $data['total_amount'],
            'status' => $data['status'] ?? 'pending',
            'locale' => $data['locale'] ?? 'en'
        ]);
        return $this->db->lastInsertId();
    }

    public function allByRestaurant(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE restaurant_id = :restaurant_id ORDER BY created_at DESC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $orderId, string $status)
    {
        $stmt = $this->db->prepare('UPDATE orders SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $orderId]);
    }

    public function report(int $restaurantId, string $from, string $to)
    {
        $stmt = $this->db->prepare('SELECT DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as total FROM orders WHERE restaurant_id = :restaurant_id AND created_at BETWEEN :from AND :to GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC');
        $stmt->execute([
            'restaurant_id' => $restaurantId,
            'from' => $from,
            'to' => $to
        ]);
        return $stmt->fetchAll();
    }
}
