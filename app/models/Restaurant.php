<?php
class Restaurant extends BaseModel
{
    public function create(array $data)
    {
        $stmt = $this->db->prepare('INSERT INTO restaurants (name, slug, user_id, description, phone, address, plan_id, timezone, theme, primary_color, currency, primary_language, supported_languages, qr_table_prefix, menu_layout) VALUES (:name, :slug, :user_id, :description, :phone, :address, :plan_id, :timezone, :theme, :primary_color, :currency, :primary_language, :supported_languages, :qr_table_prefix, :menu_layout)');
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'user_id' => $data['user_id'],
            'description' => $data['description'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'plan_id' => $data['plan_id'] ?? 1,
            'timezone' => $data['timezone'] ?? 'Europe/Istanbul',
            'theme' => $data['theme'] ?? 'emerald',
            'primary_color' => $data['primary_color'] ?? '#22c55e',
            'currency' => $data['currency'] ?? 'TRY',
            'primary_language' => $data['primary_language'] ?? 'tr',
            'supported_languages' => json_encode($data['supported_languages'] ?? ['tr','en','ar','fr']),
            'qr_table_prefix' => $data['qr_table_prefix'] ?? 'Masa',
            'menu_layout' => $data['menu_layout'] ?? 'modern',
        ]);
        return $this->db->lastInsertId();
    }

    public function findBySlug(string $slug)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurants WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $restaurant = $stmt->fetch();
        if ($restaurant && isset($restaurant['supported_languages']) && !is_array($restaurant['supported_languages'])) {
            $restaurant['supported_languages'] = json_decode($restaurant['supported_languages'], true) ?: [];
        }
        return $restaurant;
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurants WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $restaurant = $stmt->fetch();
        if ($restaurant && isset($restaurant['supported_languages']) && !is_array($restaurant['supported_languages'])) {
            $restaurant['supported_languages'] = json_decode($restaurant['supported_languages'], true) ?: [];
        }
        return $restaurant;
    }

    public function all()
    {
        return $this->db->query('SELECT r.*, u.email, p.name AS plan FROM restaurants r INNER JOIN users u ON u.restaurant_id = r.id LEFT JOIN plans p ON p.id = r.plan_id ORDER BY r.created_at DESC')->fetchAll();
    }

    public function updateStatus(int $id, string $status)
    {
        $stmt = $this->db->prepare('UPDATE restaurants SET status = :status, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateTheme(int $id, array $theme)
    {
        $stmt = $this->db->prepare('UPDATE restaurants SET theme = :theme, primary_color = :primary_color, menu_layout = :menu_layout, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'theme' => $theme['theme'],
            'primary_color' => $theme['primary_color'],
            'menu_layout' => $theme['menu_layout'] ?? 'modern',
            'id' => $id,
        ]);
    }

    public function updateBranding(int $id, array $branding)
    {
        $stmt = $this->db->prepare('UPDATE restaurants SET logo_url = :logo_url, favicon_url = :favicon_url, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'logo_url' => $branding['logo_url'] ?? null,
            'favicon_url' => $branding['favicon_url'] ?? null,
            'id' => $id,
        ]);
    }

    public function updateSettings(int $id, array $settings)
    {
        $fields = [
            'name', 'description', 'phone', 'address', 'timezone', 'currency', 'primary_language', 'qr_table_prefix', 'theme', 'primary_color'
        ];
        $optionalJson = [
            'supported_languages' => fn($value) => json_encode(array_values(array_unique(array_filter($value))))
        ];
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $field) {
            if (array_key_exists($field, $settings)) {
                $set[] = "$field = :$field";
                $params[$field] = $settings[$field];
            }
        }
        foreach ($optionalJson as $field => $transform) {
            if (array_key_exists($field, $settings)) {
                $set[] = "$field = :$field";
                $params[$field] = $transform($settings[$field]);
            }
        }
        if (!$set) {
            return false;
        }
        $set[] = 'updated_at = NOW()';
        $sql = 'UPDATE restaurants SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function findByTableToken(string $slug, string $token)
    {
        $stmt = $this->db->prepare('SELECT r.*, t.id AS table_id, t.name AS table_name, t.slug AS table_slug, t.qr_token FROM restaurants r INNER JOIN restaurant_tables t ON t.restaurant_id = r.id WHERE r.slug = :slug AND t.qr_token = :token LIMIT 1');
        $stmt->execute(['slug' => $slug, 'token' => $token]);
        $row = $stmt->fetch();
        if ($row && isset($row['supported_languages']) && !is_array($row['supported_languages'])) {
            $row['supported_languages'] = json_decode($row['supported_languages'], true) ?: [];
        }
        return $row;
    }
}
