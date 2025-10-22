<?php
require_once __DIR__ . '/helpers.php';

class MenuService
{
    public function getMenu(): array
    {
        $pdo = db();
        $categories = $pdo->query('SELECT id, name, description, sort_order FROM categories ORDER BY sort_order, name')->fetchAll();
        $categoryIds = array_column($categories, 'id');
        $products = [];
        if ($categoryIds) {
            $stmt = $pdo->prepare('SELECT id, category_id, name, description, price, image_path, is_active FROM products WHERE category_id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ') ORDER BY sort_order, name');
            $stmt->execute($categoryIds);
            $products = $stmt->fetchAll();
        }
        $grouped = [];
        foreach ($products as $product) {
            if (!$product['is_active']) {
                continue;
            }
            $grouped[$product['category_id']][] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => (float)$product['price'],
                'image' => $product['image_path'] ? $this->buildAssetUrl($product['image_path']) : 'https://via.placeholder.com/640x480?text=Menü',
            ];
        }

        $branding = [];
        $settings = $pdo->query('SELECT `key`, `value` FROM settings')->fetchAll();
        foreach ($settings as $setting) {
            $branding[$setting['key']] = $setting['value'];
        }

        foreach ($categories as &$category) {
            $category['products'] = $grouped[$category['id']] ?? [];
            $category['id'] = (int)$category['id'];
        }

        return [
            'branding' => $branding,
            'categories' => $categories,
        ];
    }

    public function findTableByToken(string $token): ?array
    {
        $stmt = db()->prepare('SELECT id, name, token FROM tables WHERE token = ?');
        $stmt->execute([$token]);
        $table = $stmt->fetch();
        if (!$table) {
            return null;
        }
        return [
            'id' => (int)$table['id'],
            'name' => $table['name'],
            'token' => $table['token'],
        ];
    }

    private function buildAssetUrl(string $path): string
    {
        if (preg_match('/^https?:\/\//', $path) || str_starts_with($path, '//')) {
            return $path;
        }
        $base = config('base_url');
        if (str_starts_with($path, '/')) {
            return rtrim($base, '/') . $path;
        }
        $relative = ltrim(str_replace(__DIR__ . '/..', '', $path), '/');
        return rtrim($base, '/') . '/' . $relative;
    }
}
