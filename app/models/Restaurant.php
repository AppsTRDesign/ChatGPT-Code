<?php
class Restaurant extends BaseModel
{
    public function create(array $data)
    {
        $config = require __DIR__ . '/../config/config.php';
        $defaultQrToken = $config['api']['qr_default_token'] ?? null;
        $stmt = $this->db->prepare('INSERT INTO restaurants (name, slug, user_id, description, phone, address, plan_id, timezone, theme, primary_color, currency, primary_language, supported_languages, logo_url, favicon_url, qr_token, qr_color, qr_background, qr_width, qr_height, qr_transparent, qr_format, qr_logo_url, qr_table_prefix, menu_layout) VALUES (:name, :slug, :user_id, :description, :phone, :address, :plan_id, :timezone, :theme, :primary_color, :currency, :primary_language, :supported_languages, :logo_url, :favicon_url, :qr_token, :qr_color, :qr_background, :qr_width, :qr_height, :qr_transparent, :qr_format, :qr_logo_url, :qr_table_prefix, :menu_layout)');
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
            'logo_url' => $data['logo_url'] ?? null,
            'favicon_url' => $data['favicon_url'] ?? null,
            'qr_token' => $data['qr_token'] ?? $defaultQrToken,
            'qr_color' => $data['qr_color'] ?? '#000000',
            'qr_background' => $data['qr_background'] ?? '#FFFFFF',
            'qr_width' => $data['qr_width'] ?? 420,
            'qr_height' => $data['qr_height'] ?? 420,
            'qr_transparent' => !empty($data['qr_transparent']) ? 1 : 0,
            'qr_format' => in_array($data['qr_format'] ?? 'png', ['png','svg','jpg'], true) ? $data['qr_format'] : 'png',
            'qr_logo_url' => $data['qr_logo_url'] ?? null,
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
        if ($restaurant) {
            $restaurant['qr_transparent'] = (bool)($restaurant['qr_transparent'] ?? false);
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
        if ($restaurant) {
            $restaurant['qr_transparent'] = (bool)($restaurant['qr_transparent'] ?? false);
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
        $fields = [];
        $params = ['id' => $id];
        foreach (['logo_url', 'favicon_url', 'qr_logo_url'] as $field) {
            if (array_key_exists($field, $branding)) {
                $fields[] = "$field = :$field";
                $params[$field] = $branding[$field];
            }
        }
        if (!$fields) {
            return false;
        }
        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE restaurants SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateSettings(int $id, array $settings)
    {
        $fields = [
            'name', 'description', 'phone', 'address', 'timezone', 'currency', 'primary_language', 'qr_table_prefix', 'theme', 'primary_color',
            'qr_token', 'qr_color', 'qr_background', 'qr_width', 'qr_height', 'qr_transparent', 'qr_format'
        ];
        $optionalJson = [
            'supported_languages' => fn($value) => json_encode(array_values(array_unique(array_filter($value))))
        ];
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $field) {
            if (array_key_exists($field, $settings)) {
                $set[] = "$field = :$field";
                $params[$field] = $this->normalizeField($field, $settings[$field]);
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

    private function normalizeField(string $field, $value)
    {
        switch ($field) {
            case 'qr_width':
            case 'qr_height':
                $int = (int)$value;
                return max(120, min($int ?: 0, 1000));
            case 'qr_transparent':
                return !empty($value) ? 1 : 0;
            case 'qr_format':
                $format = strtolower((string)$value);
                return in_array($format, ['png', 'svg', 'jpg'], true) ? $format : 'png';
            case 'qr_color':
            case 'qr_background':
            case 'primary_color':
                return strtoupper((string)$value);
            case 'qr_token':
                return trim((string)$value);
            case 'currency':
                return strtoupper((string)$value);
            default:
                return $value;
        }
    }

    public function findByTableToken(string $slug, string $token)
    {
        $stmt = $this->db->prepare('SELECT r.*, t.id AS table_id, t.name AS table_name, t.slug AS table_slug, t.qr_token FROM restaurants r INNER JOIN restaurant_tables t ON t.restaurant_id = r.id WHERE r.slug = :slug AND t.qr_token = :token LIMIT 1');
        $stmt->execute(['slug' => $slug, 'token' => $token]);
        $row = $stmt->fetch();
        if ($row && isset($row['supported_languages']) && !is_array($row['supported_languages'])) {
            $row['supported_languages'] = json_decode($row['supported_languages'], true) ?: [];
        }
        if ($row) {
            $row['qr_transparent'] = (bool)($row['qr_transparent'] ?? false);
        }
        return $row;
    }
}
