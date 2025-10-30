<?php
class Product extends BaseModel
{
    public function allByRestaurant(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.restaurant_id = :restaurant_id ORDER BY p.sort_order ASC, p.name ASC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        $products = $stmt->fetchAll();
        foreach ($products as &$product) {
            if (isset($product['nutrition']) && !is_array($product['nutrition'])) {
                $product['nutrition'] = json_decode($product['nutrition'], true) ?: [];
            }
        }
        return $products;
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO products (restaurant_id, category_id, name, description, price, currency, image_url, sort_order, is_available, nutrition) VALUES (:restaurant_id, :category_id, :name, :description, :price, :currency, :image_url, :sort_order, :is_available, :nutrition)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'TRY',
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_available' => $data['is_available'] ?? 1,
            'nutrition' => isset($data['nutrition']) ? json_encode($data['nutrition']) : null,
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->db->prepare('UPDATE products SET name = :name, description = :description, price = :price, currency = :currency, image_url = :image_url, sort_order = :sort_order, is_available = :is_available, nutrition = :nutrition WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'TRY',
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_available' => $data['is_available'] ?? 1,
            'nutrition' => isset($data['nutrition']) ? json_encode($data['nutrition']) : null,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
