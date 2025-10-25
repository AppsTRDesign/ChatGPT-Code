<?php

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];

    protected static function db(): PDO
    {
        return Database::pdo();
    }

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        $conditions = [];
        $params = [];

        foreach ($filters as $field => $value) {
            if (!static::isFilterable($field)) {
                continue;
            }

            $placeholder = str_replace('.', '_', $field);
            $conditions[] = sprintf('%s = :%s', $field, $placeholder);
            $params[$placeholder] = $value;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return array_map([static::class, 'transformRecord'], $stmt->fetchAll() ?: []);
    }

    public static function first(array $filters): ?array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        $conditions = [];
        $params = [];

        foreach ($filters as $field => $value) {
            if (!static::isFilterable($field)) {
                continue;
            }

            $placeholder = str_replace('.', '_', $field);
            $conditions[] = sprintf('%s = :%s', $field, $placeholder);
            $params[$placeholder] = $value;
        }

        if (!$conditions) {
            return null;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' LIMIT 1';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $record = $stmt->fetch();

        return $record ? static::transformRecord($record) : null;
    }

    public static function find(int $id): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();

        return $record ? static::transformRecord($record) : null;
    }

    public static function create(array $attributes): int
    {
        $data = static::filterFillable($attributes);
        if (!$data) {
            throw new \InvalidArgumentException('No valid attributes provided for insert');
        }

        $fields = array_keys($data);
        $placeholders = array_map(fn ($field) => ':' . $field, $fields);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute($data);

        return (int) static::db()->lastInsertId();
    }

    public static function updateById(int $id, array $attributes): bool
    {
        $data = static::filterFillable($attributes);
        if (!$data) {
            return false;
        }

        $fields = array_keys($data);
        $sets = array_map(fn ($field) => sprintf('%s = :%s', $field, $field), $fields);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :primary_id',
            static::$table,
            implode(', ', $sets),
            static::$primaryKey
        );

        $data['primary_id'] = $id;

        $stmt = static::db()->prepare($sql);

        return $stmt->execute($data);
    }

    public static function deleteById(int $id): bool
    {
        $stmt = static::db()->prepare('DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id');
        return $stmt->execute(['id' => $id]);
    }

    protected static function filterFillable(array $attributes): array
    {
        if (!static::$fillable) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip(static::$fillable));
    }

    protected static function isFilterable(string $field): bool
    {
        if ($field === static::$primaryKey) {
            return true;
        }

        if (in_array($field, static::$fillable, true)) {
            return true;
        }

        return str_contains($field, '.');
    }

    protected static function transformRecord(array $record): array
    {
        return $record;
    }
}
