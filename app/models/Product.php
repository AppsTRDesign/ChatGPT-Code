<?php
class Product extends BaseModel
{
    public function allByRestaurant(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT p.*, c.name as category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.restaurant_id = :restaurant_id ORDER BY p.sort_order ASC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO products (restaurant_id, category_id, name, description, price, image_url, sort_order, is_available) VALUES (:restaurant_id, :category_id, :name, :description, :price, :image_url, :sort_order, :is_available)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_available' => $data['is_available'] ?? 1,
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->db->prepare('UPDATE products SET name = :name, description = :description, price = :price, image_url = :image_url, sort_order = :sort_order, is_available = :is_available WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_available' => $data['is_available'] ?? 1,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
