<?php
class WaiterCall extends BaseModel
{
    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO waiter_calls (restaurant_id, table_id, table_number, status) VALUES (:restaurant_id, :table_id, :table_number, :status)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'table_id' => $data['table_id'],
            'table_number' => $data['table_number'],
            'status' => $data['status'] ?? 'pending',
        ]);
        return $this->db->lastInsertId();
    }

    public function listActive(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM waiter_calls WHERE restaurant_id = :restaurant_id AND status != "resolved" ORDER BY created_at DESC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status)
    {
        $stmt = $this->db->prepare('UPDATE waiter_calls SET status = :status, resolved_at = CASE WHEN :status = "resolved" THEN NOW() ELSE resolved_at END WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
