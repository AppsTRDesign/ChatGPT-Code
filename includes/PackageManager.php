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

    public static function create(string $name, string $description, int $monthlyLimit, int $durationDays, string $features, float $price, array $qrTypes = []): bool
    {
        $qrFeatures = QrService::encodeTypeList($qrTypes);

        $stmt = Helpers::db()->prepare('INSERT INTO packages (name, description, monthly_limit, duration_days, features, price, is_active, qr_features) VALUES (:name, :description, :monthly_limit, :duration_days, :features, :price, 1, :qr_features)');
        return $stmt->execute([
            'name' => $name,
            'description' => $description,
            'monthly_limit' => $monthlyLimit,
            'duration_days' => $durationDays,
            'features' => $features,
            'price' => $price,
            'qr_features' => $qrFeatures,
        ]);
    }

    public static function update(int $id, string $name, string $description, int $monthlyLimit, int $durationDays, string $features, float $price, bool $isActive, array $qrTypes = []): bool
    {
        $qrFeatures = QrService::encodeTypeList($qrTypes);

        $stmt = Helpers::db()->prepare('UPDATE packages SET name = :name, description = :description, monthly_limit = :monthly_limit, duration_days = :duration_days, features = :features, price = :price, is_active = :is_active, qr_features = :qr_features WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'monthly_limit' => $monthlyLimit,
            'duration_days' => $durationDays,
            'features' => $features,
            'price' => $price,
            'is_active' => $isActive ? 1 : 0,
            'qr_features' => $qrFeatures,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Helpers::db()->prepare('DELETE FROM packages WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function toggle(int $id): bool
    {
        $stmt = Helpers::db()->prepare('UPDATE packages SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
