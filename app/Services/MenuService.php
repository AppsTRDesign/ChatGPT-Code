<?php

namespace App\Services;

use Core\Database;
use PDO;

class MenuService
{
    private PDO $db;
    private int $restaurantId;

    public function __construct(int $restaurantId = 1)
    {
        $this->db = Database::connection();
        $this->restaurantId = $restaurantId;
    }

    public function categories(): array
    {
        $statement = $this->db->prepare('SELECT id, name, icon, image FROM categories WHERE restaurant_id = ? ORDER BY name');
        $statement->execute([$this->restaurantId]);
        $categories = $statement->fetchAll() ?: [];

        return array_map(function ($category) {
            $category['icon'] = $this->cleanIcon($category['icon'] ?? null);
            $category['image'] = $this->mediaUrl($category['image'] ?? null);
            return $category;
        }, $categories);
    }

    public function saveCategory(array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $name = trim($payload['name'] ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('Kategori adı zorunludur.');
        }

        $icon = $this->storeIcon($payload['icon'] ?? null);
        $image = $this->normalizeMedia($payload['image'] ?? null);

        if ($id > 0) {
            $statement = $this->db->prepare('UPDATE categories SET name = ?, icon = ?, image = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?');
            $statement->execute([
                $name,
                $icon,
                $image,
                $id,
                $this->restaurantId,
            ]);
        } else {
            $statement = $this->db->prepare('INSERT INTO categories (restaurant_id, name, icon, image) VALUES (?, ?, ?, ?)');
            $statement->execute([
                $this->restaurantId,
                $name,
                $icon,
                $image,
            ]);
            $id = (int)$this->db->lastInsertId();
        }

        return $this->category($id);
    }

    public function category(int $id): array
    {
        $statement = $this->db->prepare('SELECT id, name, icon, image FROM categories WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);
        $category = $statement->fetch() ?: [];
        if (!$category) {
            return [];
        }

        $category['icon'] = $this->cleanIcon($category['icon'] ?? null);
        $category['image'] = $this->mediaUrl($category['image'] ?? null);

        return $category;
    }

    public function deleteCategory(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM categories WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);
    }

    public function products(): array
    {
        $statement = $this->db->prepare('SELECT p.id, p.category_id, p.name, p.description, p.price, p.image, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.restaurant_id = ? ORDER BY p.created_at DESC');
        $statement->execute([$this->restaurantId]);
        $products = $statement->fetchAll() ?: [];

        foreach ($products as &$product) {
            $product['price'] = (float)$product['price'];
            $product['image'] = $this->mediaUrl($product['image'] ?? null);
            $product['variants'] = $this->variants((int)$product['id']);
        }

        return $products;
    }

    public function product(int $id): array
    {
        $statement = $this->db->prepare('SELECT id, category_id, name, description, price, image FROM products WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);
        $product = $statement->fetch();
        if (!$product) {
            return [];
        }
        $product['price'] = (float)$product['price'];
        $product['image'] = $this->mediaUrl($product['image'] ?? null);
        $product['variants'] = $this->variants($id);
        return $product;
    }

    public function saveProduct(array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $name = trim($payload['name'] ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('Ürün adı zorunludur.');
        }

        $categoryId = (int)($payload['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new \InvalidArgumentException('Kategori seçilmelidir.');
        }

        $price = (float)($payload['price'] ?? 0);
        if ($price <= 0) {
            throw new \InvalidArgumentException('Fiyat sıfırdan büyük olmalıdır.');
        }

        $image = $this->normalizeMedia($payload['image'] ?? null);

        if ($id > 0) {
            $statement = $this->db->prepare('UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, image = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?');
            $statement->execute([
                $categoryId,
                $name,
                $payload['description'] ?? null,
                $price,
                $image,
                $id,
                $this->restaurantId,
            ]);
        } else {
            $statement = $this->db->prepare('INSERT INTO products (restaurant_id, category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?, ?)');
            $statement->execute([
                $this->restaurantId,
                $categoryId,
                $name,
                $payload['description'] ?? null,
                $price,
                $image,
            ]);
            $id = (int)$this->db->lastInsertId();
        }

        $this->syncVariants($id, $payload['variants'] ?? []);

        return $this->product($id);
    }

    public function deleteProduct(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM products WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);
    }

    public function variants(int $productId): array
    {
        $statement = $this->db->prepare('SELECT id, name, price FROM product_variants WHERE product_id = ? ORDER BY price');
        $statement->execute([$productId]);
        return array_map(static fn($variant) => [
            'id' => (int)$variant['id'],
            'name' => $variant['name'],
            'price' => (float)$variant['price'],
        ], $statement->fetchAll() ?: []);
    }

    private function syncVariants(int $productId, array $variants): void
    {
        $existing = $this->variants($productId);
        $existingIds = array_column($existing, 'id');

        $idsToKeep = [];
        foreach ($variants as $variant) {
            $variantName = trim($variant['name'] ?? '');
            $variantPrice = isset($variant['price']) ? (float)$variant['price'] : null;
            if ($variantName === '' || $variantPrice === null) {
                continue;
            }

            $variantId = (int)($variant['id'] ?? 0);
            if ($variantId > 0 && in_array($variantId, $existingIds, true)) {
                $statement = $this->db->prepare('UPDATE product_variants SET name = ?, price = ?, updated_at = NOW() WHERE id = ? AND product_id = ?');
                $statement->execute([$variantName, $variantPrice, $variantId, $productId]);
                $idsToKeep[] = $variantId;
            } else {
                $statement = $this->db->prepare('INSERT INTO product_variants (product_id, name, price) VALUES (?, ?, ?)');
                $statement->execute([$productId, $variantName, $variantPrice]);
                $idsToKeep[] = (int)$this->db->lastInsertId();
            }
        }

        if (!empty($existingIds)) {
            $deleteIds = array_diff($existingIds, $idsToKeep);
            if (!empty($deleteIds)) {
                $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                $statement = $this->db->prepare("DELETE FROM product_variants WHERE product_id = ? AND id IN ({$placeholders})");
                $statement->execute(array_merge([$productId], array_values($deleteIds)));
            }
        }
    }

    private function normalizeMedia(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, BASE_URL)) {
            $value = substr($value, strlen(BASE_URL));
        }
        return ltrim($value, '/');
    }

    private function mediaUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }
        $value = ltrim($value, '/');
        if ($value === '') {
            return null;
        }
        return rtrim(BASE_URL, '/') . '/' . $value;
    }

    private function storeIcon(?string $icon): ?string
    {
        if (!$icon) {
            return null;
        }

        $icon = trim($icon);
        if ($icon === '') {
            return null;
        }

        $icon = preg_replace('/[^a-z0-9\s\-_:]/i', '', $icon);
        $icon = preg_replace('/\s+/', ' ', $icon);

        return $icon === '' ? null : $icon;
    }

    private function cleanIcon(?string $icon): ?string
    {
        $icon = $this->storeIcon($icon);
        if (!$icon) {
            return null;
        }

        if (str_contains($icon, ' ')) {
            return $icon;
        }

        if (str_starts_with($icon, 'bx-')) {
            return 'bx ' . $icon;
        }

        if (str_starts_with($icon, 'fa-')) {
            return 'fa-solid ' . $icon;
        }

        return $icon;
    }
}
