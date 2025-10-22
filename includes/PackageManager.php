<?php

namespace App;

class PackageManager
{
    public static function allActive(): array
    {
        $stmt = Helpers::db()->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Helpers::db()->prepare('SELECT * FROM packages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $package = $stmt->fetch();
        return $package ?: null;
    }

    public static function create(string $name, string $description, int $monthlyLimit, float $price): bool
    {
        $stmt = Helpers::db()->prepare('INSERT INTO packages (name, description, monthly_limit, price, is_active) VALUES (:name, :description, :monthly_limit, :price, 1)');
        return $stmt->execute([
            'name' => $name,
            'description' => $description,
            'monthly_limit' => $monthlyLimit,
            'price' => $price,
        ]);
    }
}
