<?php
class Restaurant extends BaseModel
{
    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO restaurants (name, slug, user_id, description, phone, address, plan_id, timezone, theme, primary_color) VALUES (:name, :slug, :user_id, :description, :phone, :address, :plan_id, :timezone, :theme, :primary_color)');
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'user_id' => $data['user_id'],
            'description' => $data['description'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'plan_id' => $data['plan_id'] ?? 1,
            'timezone' => $data['timezone'] ?? 'Europe/Istanbul',
            'theme' => $data['theme'] ?? 'light',
            'primary_color' => $data['primary_color'] ?? '#1d4ed8'
        ]);
        return $this->db->lastInsertId();
    }

    public function findBySlug(string $slug)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurants WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch();
    }

    public function all()
    {
        return $this->db->query('SELECT r.*, u.email, p.name AS plan FROM restaurants r INNER JOIN users u ON u.restaurant_id = r.id LEFT JOIN plans p ON p.id = r.plan_id ORDER BY r.created_at DESC')->fetchAll();
    }

    public function updateStatus(int $id, string $status)
    {
        $stmt = $this->db->prepare('UPDATE restaurants SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurants WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}
