<?php
class Category extends BaseModel
{
    public function allByRestaurant(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE restaurant_id = :restaurant_id ORDER BY sort_order ASC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO categories (restaurant_id, name, description, sort_order) VALUES (:restaurant_id, :name, :description, :sort_order)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->db->prepare('UPDATE categories SET name = :name, description = :description, sort_order = :sort_order WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
