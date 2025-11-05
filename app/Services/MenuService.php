<?php

namespace App\Services;

use Core\Database;
use PDO;

class MenuService
{
    private PDO $db;
    private int $restaurantId;
    private SettingsService $settings;
    private array $languages = [];
    private array $languageCodes = [];
    private string $defaultLanguage;
    private ?string $targetLanguage;
    private array $menuTranslations = [];
    private bool $translationsDirty = false;
    private array $categoryTranslationCache = [];

    public function __construct(int $restaurantId = 1, ?string $language = null)
    {
        $this->db = Database::connection();
        $this->restaurantId = $restaurantId;
        $this->settings = new SettingsService($this->restaurantId);
        $this->languages = $this->settings->languages();
        $this->languageCodes = array_values(array_filter(array_map(static fn($language) => strtolower($language['code'] ?? ''), $this->languages)));
        if (empty($this->languageCodes)) {
            $this->languageCodes = ['tr'];
        }
        $this->defaultLanguage = strtolower($this->settings->currentLanguage() ?: $this->languageCodes[0]);
        if (!in_array($this->defaultLanguage, $this->languageCodes, true)) {
            array_unshift($this->languageCodes, $this->defaultLanguage);
            $this->languageCodes = array_values(array_unique($this->languageCodes));
        }
        $this->targetLanguage = $language ? strtolower($language) : null;
        if ($this->targetLanguage && !in_array($this->targetLanguage, $this->languageCodes, true)) {
            $this->targetLanguage = $this->defaultLanguage;
        }
        $this->menuTranslations = $this->settings->menuTranslations();
        foreach (['categories', 'products', 'variants'] as $bucket) {
            if (!isset($this->menuTranslations[$bucket]) || !is_array($this->menuTranslations[$bucket])) {
                $this->menuTranslations[$bucket] = [];
            }
        }
    }

    public function dailyMenu(): array
    {
        $settings = new SettingsService($this->restaurantId);
        $items = $settings->dailyMenu();

        if (empty($items)) {
            return [];
        }

        $productIds = array_values(array_unique(array_filter(array_map(static fn($item) => (int)($item['product_id'] ?? 0), $items))));

        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $statement = $this->db->prepare("SELECT id, name, description, price, image FROM products WHERE restaurant_id = ? AND id IN ({$placeholders})");
        $statement->execute(array_merge([$this->restaurantId], $productIds));
        $products = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $productMap = [];
        foreach ($products as $product) {
            $mapped = $this->mapProduct($product, false);
            $productMap[(int)$mapped['id']] = $mapped;
        }

        $result = [];
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            if (!isset($productMap[$productId])) {
                continue;
            }

            $product = $productMap[$productId];
            $result[] = [
                'id' => $item['id'],
                'product_id' => $productId,
                'headline' => $item['headline'] ?: $product['name'],
                'tagline' => $item['tagline'] ?? '',
                'badge' => $item['badge'] ?? '',
                'position' => (int)($item['position'] ?? 0),
                'product' => [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => (float)$product['price'],
                    'image' => $product['image'],
                ],
            ];
        }

        usort($result, static fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return $result;
    }

    public function categories(): array
    {
        $statement = $this->db->prepare('SELECT id, name, icon, image FROM categories WHERE restaurant_id = ? ORDER BY name');
        $statement->execute([$this->restaurantId]);
        $categories = $statement->fetchAll() ?: [];

        return array_map(function ($category) {
            $category['icon'] = $this->cleanIcon($category['icon'] ?? null);
            $category['image'] = $this->mediaUrl($category['image'] ?? null);
            $categoryId = (int)($category['id'] ?? 0);
            $stored = $this->getTranslations('categories', $categoryId);
            $map = $this->buildTranslationMap($stored, ['name' => $category['name'] ?? '']);
            $category['translations'] = $map;
            $category['name'] = $this->resolveTranslatedValue($map, 'name', $category['name'] ?? '');
            $this->categoryTranslationCache[$categoryId] = $map;
            return $category;
        }, $categories);
    }

    public function saveCategory(array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $translations = is_array($payload['translations'] ?? []) ? $payload['translations'] : [];
        $defaultName = trim($translations[$this->defaultLanguage]['name'] ?? $payload['name'] ?? '');
        if ($defaultName === '') {
            throw new \InvalidArgumentException('Kategori adı zorunludur.');
        }
        $name = $defaultName;

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

        $preparedTranslations = $this->prepareStoredTranslations($translations, ['name']);
        $this->setTranslations('categories', $id, $preparedTranslations);
        unset($this->categoryTranslationCache[$id]);
        $this->persistTranslations();

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
        $stored = $this->getTranslations('categories', $id);
        $map = $this->buildTranslationMap($stored, ['name' => $category['name'] ?? '']);
        $category['translations'] = $map;
        $category['name'] = $this->resolveTranslatedValue($map, 'name', $category['name'] ?? '');
        $this->categoryTranslationCache[$id] = $map;

        return $category;
    }

    public function deleteCategory(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM categories WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);
        $this->removeTranslations('categories', $id);
        unset($this->categoryTranslationCache[$id]);
        $this->persistTranslations();
    }

    public function products(): array
    {
        $statement = $this->db->prepare('SELECT p.id, p.category_id, p.name, p.description, p.price, p.image, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.restaurant_id = ? ORDER BY p.created_at DESC');
        $statement->execute([$this->restaurantId]);
        $products = $statement->fetchAll() ?: [];

        foreach ($products as &$product) {
            $product = $this->mapProduct($product);
        }
        unset($product);

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
        return $this->mapProduct($product);
    }

    public function saveProduct(array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $translations = is_array($payload['translations'] ?? []) ? $payload['translations'] : [];
        $defaultName = trim($translations[$this->defaultLanguage]['name'] ?? $payload['name'] ?? '');
        if ($defaultName === '') {
            throw new \InvalidArgumentException('Ürün adı zorunludur.');
        }
        $defaultDescription = trim($translations[$this->defaultLanguage]['description'] ?? $payload['description'] ?? '');
        $name = $defaultName;
        $description = $defaultDescription !== '' ? $defaultDescription : null;

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
                $description,
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
                $description,
                $price,
                $image,
            ]);
            $id = (int)$this->db->lastInsertId();
        }

        $preparedTranslations = $this->prepareStoredTranslations($translations, ['name', 'description']);
        $this->setTranslations('products', $id, $preparedTranslations);

        $variants = is_array($payload['variants'] ?? null) ? $payload['variants'] : [];
        $this->syncVariants($id, $variants);
        $this->persistTranslations();

        return $this->product($id);
    }

    public function deleteProduct(int $id): void
    {
        $variantStatement = $this->db->prepare('SELECT id FROM product_variants WHERE product_id = ?');
        $variantStatement->execute([$id]);
        $variantIds = array_map('intval', $variantStatement->fetchAll(PDO::FETCH_COLUMN) ?: []);

        $statement = $this->db->prepare('DELETE FROM products WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);
        $this->removeTranslations('products', $id);
        foreach ($variantIds as $variantId) {
            $this->removeTranslations('variants', $variantId);
        }
        $this->persistTranslations();
    }

    public function variants(int $productId): array
    {
        $statement = $this->db->prepare('SELECT id, name, price FROM product_variants WHERE product_id = ? ORDER BY price');
        $statement->execute([$productId]);
        $variants = $statement->fetchAll() ?: [];

        return array_map(function ($variant) {
            $variantId = (int)($variant['id'] ?? 0);
            $price = (float)($variant['price'] ?? 0);
            $stored = $this->getTranslations('variants', $variantId);
            $map = $this->buildTranslationMap($stored, ['name' => $variant['name'] ?? '']);
            $variant['translations'] = $map;
            $variant['name'] = $this->resolveTranslatedValue($map, 'name', $variant['name'] ?? '');

            return [
                'id' => $variantId,
                'name' => $variant['name'],
                'price' => $price,
                'translations' => $map,
            ];
        }, $variants);
    }

    private function syncVariants(int $productId, array $variants): void
    {
        $existing = $this->variants($productId);
        $existingIds = array_column($existing, 'id');

        $idsToKeep = [];
        foreach ($variants as $variant) {
            $variantTranslations = is_array($variant['translations'] ?? []) ? $variant['translations'] : [];
            $defaultName = trim($variantTranslations[$this->defaultLanguage]['name'] ?? $variant['name'] ?? '');
            $variantPrice = isset($variant['price']) ? (float)$variant['price'] : null;
            if ($defaultName === '' || $variantPrice === null) {
                continue;
            }

            $variantId = (int)($variant['id'] ?? 0);
            if ($variantId > 0 && in_array($variantId, $existingIds, true)) {
                $statement = $this->db->prepare('UPDATE product_variants SET name = ?, price = ?, updated_at = NOW() WHERE id = ? AND product_id = ?');
                $statement->execute([$defaultName, $variantPrice, $variantId, $productId]);
            } else {
                $statement = $this->db->prepare('INSERT INTO product_variants (product_id, name, price) VALUES (?, ?, ?)');
                $statement->execute([$productId, $defaultName, $variantPrice]);
                $variantId = (int)$this->db->lastInsertId();
            }
            $preparedVariantTranslations = $this->prepareStoredTranslations($variantTranslations, ['name']);
            $this->setTranslations('variants', $variantId, $preparedVariantTranslations);
            $idsToKeep[] = $variantId;
        }

        if (!empty($existingIds)) {
            $deleteIds = array_diff($existingIds, $idsToKeep);
            if (!empty($deleteIds)) {
                $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                $statement = $this->db->prepare("DELETE FROM product_variants WHERE product_id = ? AND id IN ({$placeholders})");
                $statement->execute(array_merge([$productId], array_values($deleteIds)));
                foreach ($deleteIds as $deleteId) {
                    $this->removeTranslations('variants', (int)$deleteId);
                }
            }
        }
    }

    private function mapProduct(array $product, bool $includeVariants = true): array
    {
        $productId = (int)($product['id'] ?? 0);
        $baseName = $product['name'] ?? '';
        $baseDescription = $product['description'] ?? '';

        $product['price'] = isset($product['price']) ? (float)$product['price'] : 0.0;
        $product['image'] = $this->mediaUrl($product['image'] ?? null);

        $stored = $this->getTranslations('products', $productId);
        $map = $this->buildTranslationMap($stored, [
            'name' => $baseName,
            'description' => $baseDescription,
        ]);

        $product['translations'] = $map;
        $product['name'] = $this->resolveTranslatedValue($map, 'name', $baseName);
        $product['description'] = $this->resolveTranslatedValue($map, 'description', $baseDescription);

        if (array_key_exists('category_name', $product)) {
            $product['category_name'] = $this->translateCategoryName((int)($product['category_id'] ?? 0), $product['category_name']);
        }

        if ($includeVariants) {
            $product['variants'] = $this->variants($productId);
        }

        return $product;
    }

    private function translateCategoryName(int $categoryId, ?string $fallback = ''): string
    {
        $baseName = $fallback ?? '';
        if ($categoryId <= 0) {
            return $baseName;
        }

        if (!isset($this->categoryTranslationCache[$categoryId])) {
            $stored = $this->getTranslations('categories', $categoryId);
            if ($baseName === '') {
                $statement = $this->db->prepare('SELECT name FROM categories WHERE restaurant_id = ? AND id = ?');
                $statement->execute([$this->restaurantId, $categoryId]);
                $baseName = (string)$statement->fetchColumn();
            }
            $map = $this->buildTranslationMap($stored, ['name' => $baseName]);
            $this->categoryTranslationCache[$categoryId] = $map;
        } else {
            $map = $this->categoryTranslationCache[$categoryId];
            if ($baseName !== '' && ($map[$this->defaultLanguage]['name'] ?? '') === '') {
                $map[$this->defaultLanguage]['name'] = $baseName;
                $this->categoryTranslationCache[$categoryId] = $map;
            }
        }

        $map = $this->categoryTranslationCache[$categoryId];
        return $this->resolveTranslatedValue($map, 'name', $baseName);
    }

    private function getTargetLanguage(): string
    {
        return $this->targetLanguage ?? $this->defaultLanguage;
    }

    private function getTranslations(string $type, int $id): array
    {
        if (!isset($this->menuTranslations[$type]) || !is_array($this->menuTranslations[$type])) {
            return [];
        }

        $entry = $this->menuTranslations[$type][(string)$id] ?? [];
        if (!is_array($entry)) {
            return [];
        }

        $normalized = [];
        foreach ($entry as $code => $fields) {
            $langCode = strtolower((string)$code);
            if (!in_array($langCode, $this->languageCodes, true)) {
                continue;
            }
            $normalized[$langCode] = [];
            foreach ($fields as $field => $value) {
                $normalized[$langCode][$field] = is_scalar($value) ? (string)$value : '';
            }
        }

        return $normalized;
    }

    private function prepareStoredTranslations(array $translations, array $fields): array
    {
        $prepared = [];

        foreach ($translations as $code => $values) {
            $langCode = strtolower((string)$code);
            if (!in_array($langCode, $this->languageCodes, true) || $langCode === $this->defaultLanguage) {
                continue;
            }

            $entry = [];
            foreach ($fields as $field) {
                if (!array_key_exists($field, $values)) {
                    continue;
                }
                $value = trim((string)$values[$field]);
                if ($value !== '') {
                    $entry[$field] = $value;
                }
            }

            if (!empty($entry)) {
                $prepared[$langCode] = $entry;
            }
        }

        return $prepared;
    }

    private function buildTranslationMap(array $stored, array $base): array
    {
        $map = [];
        foreach ($this->languageCodes as $code) {
            $map[$code] = [];
            foreach ($base as $field => $value) {
                if ($code === $this->defaultLanguage) {
                    $map[$code][$field] = (string)($value ?? '');
                } elseif (isset($stored[$code][$field])) {
                    $map[$code][$field] = (string)$stored[$code][$field];
                } else {
                    $map[$code][$field] = '';
                }
            }
        }

        return $map;
    }

    private function resolveTranslatedValue(array $translations, string $field, ?string $fallback): string
    {
        $language = $this->getTargetLanguage();
        $value = $translations[$language][$field] ?? null;
        if ($value !== null) {
            $trimmed = trim((string)$value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        $defaultValue = $translations[$this->defaultLanguage][$field] ?? $fallback ?? '';
        return is_string($defaultValue) ? $defaultValue : (string)$defaultValue;
    }

    private function setTranslations(string $type, int $id, array $translations): void
    {
        if (!isset($this->menuTranslations[$type]) || !is_array($this->menuTranslations[$type])) {
            $this->menuTranslations[$type] = [];
        }

        $key = (string)$id;
        if (empty($translations)) {
            if (isset($this->menuTranslations[$type][$key])) {
                unset($this->menuTranslations[$type][$key]);
                $this->translationsDirty = true;
            }
            return;
        }

        $this->menuTranslations[$type][$key] = $translations;
        $this->translationsDirty = true;
    }

    private function removeTranslations(string $type, int $id): void
    {
        if (isset($this->menuTranslations[$type][(string)$id])) {
            unset($this->menuTranslations[$type][(string)$id]);
            $this->translationsDirty = true;
        }
    }

    private function persistTranslations(): void
    {
        if (!$this->translationsDirty) {
            return;
        }
        $this->menuTranslations = $this->settings->saveMenuTranslations($this->menuTranslations);
        $this->translationsDirty = false;
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
