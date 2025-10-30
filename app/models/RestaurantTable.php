<?php
class RestaurantTable extends BaseModel
{
    public function allByRestaurant(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE restaurant_id = :restaurant_id ORDER BY created_at ASC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public function findBySlug(int $restaurantId, string $slug)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE restaurant_id = :restaurant_id AND slug = :slug LIMIT 1');
        $stmt->execute(['restaurant_id' => $restaurantId, 'slug' => $slug]);
        return $stmt->fetch();
    }

    public function slugExists(int $restaurantId, string $slug, ?int $exceptId = null): bool
    {
        $query = 'SELECT id FROM restaurant_tables WHERE restaurant_id = :restaurant_id AND slug = :slug';
        $params = ['restaurant_id' => $restaurantId, 'slug' => $slug];
        if ($exceptId) {
            $query .= ' AND id != :id';
            $params['id'] = $exceptId;
        }
        $query .= ' LIMIT 1';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO restaurant_tables (restaurant_id, name, slug, qr_token, seats, status) VALUES (:restaurant_id, :name, :slug, :qr_token, :seats, :status)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'qr_token' => $data['qr_token'],
            'seats' => $data['seats'] ?? 4,
            'status' => $data['status'] ?? 'vacant',
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->db->prepare('UPDATE restaurant_tables SET name = :name, slug = :slug, seats = :seats, status = :status WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'seats' => $data['seats'] ?? 4,
            'status' => $data['status'] ?? 'vacant',
        ]);
    }

    public function updateStatus(int $id, string $status)
    {
        $stmt = $this->db->prepare('UPDATE restaurant_tables SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare('DELETE FROM restaurant_tables WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function findByToken(string $token)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE qr_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}
