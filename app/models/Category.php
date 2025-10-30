<?php
class Category extends BaseModel
{
    public function allByRestaurant(int $restaurantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE restaurant_id = :restaurant_id ORDER BY sort_order ASC, name ASC');
        $stmt->execute(['restaurant_id' => $restaurantId]);
        return $stmt->fetchAll();
    }

    public function withProducts(int $restaurantId)
    {
        $categories = $this->allByRestaurant($restaurantId);
        $productModel = new Product();
        $products = $productModel->allByRestaurant($restaurantId);
        $indexed = [];
        foreach ($categories as $category) {
            $category['products'] = [];
            $indexed[$category['id']] = $category;
        }
        foreach ($products as $product) {
            if (!isset($indexed[$product['category_id']])) {
                continue;
            }
            $indexed[$product['category_id']]['products'][] = $product;
        }
        return array_values($indexed);
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO categories (restaurant_id, name, description, image_url, sort_order) VALUES (:restaurant_id, :name, :description, :image_url, :sort_order)');
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->db->prepare('UPDATE categories SET name = :name, description = :description, image_url = :image_url, sort_order = :sort_order WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
