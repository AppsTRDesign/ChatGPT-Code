<?php

require_once __DIR__ . '/helpers.php';

class MenuService
{
    private DataStore $menuStore;
    private DataStore $tableStore;

    public function __construct()
    {
        $this->menuStore = datastore('menu', [
            'categories' => [],
        ]);
        $this->tableStore = datastore('tables', [
            'tables' => []
        ]);
    }

    public function getMenu(): array
    {
        return $this->menuStore->all();
    }

    public function saveMenu(array $menu): array
    {
        $menu['categories'] = array_values(array_map(function ($category, $index) {
            $category['id'] = $category['id'] ?? uniqid('cat_');
            $category['position'] = $index;
            $category['products'] = array_values(array_map(function ($product, $pIndex) {
                $product['id'] = $product['id'] ?? uniqid('prd_');
                $product['position'] = $pIndex;
                return $product;
            }, $category['products'] ?? [], array_keys($category['products'] ?? [])));
            return $category;
        }, $menu['categories'] ?? [], array_keys($menu['categories'] ?? [])));

        $this->menuStore->save($menu);
        cache_clear();
        return $menu;
    }

    public function getTables(): array
    {
        return $this->tableStore->all();
    }

    public function saveTables(array $tables): array
    {
        $tables['tables'] = array_values(array_map(function ($table) {
            $table['id'] = $table['id'] ?? uniqid('tbl_');
            $table['name'] = $table['name'] ?? 'Masa';
            $table['qr_token'] = $table['qr_token'] ?? bin2hex(random_bytes(6));
            return $table;
        }, $tables['tables'] ?? []));
        $this->tableStore->save($tables);
        cache_clear();
        return $tables;
    }

    public function findTableByToken(string $token): ?array
    {
        $tables = $this->getTables();
        foreach ($tables['tables'] ?? [] as $table) {
            if (($table['qr_token'] ?? '') === $token) {
                return $table;
            }
        }
        return null;
    }
}
