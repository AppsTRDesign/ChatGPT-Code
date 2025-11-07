<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected static string $table;
    protected static array $fillable = [];

    public static function connection(): PDO
    {
        return Database::connection();
    }

    public static function all(): array
    {
        $stmt = self::connection()->query('SELECT * FROM ' . static::$table . ' ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function create(array $attributes): int
    {
        $data = array_intersect_key($attributes, array_flip(static::$fillable));
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn($key) => ':' . $key, array_keys($data)));
        $stmt = self::connection()->prepare('INSERT INTO ' . static::$table . ' (' . $columns . ') VALUES (' . $placeholders . ')');
        $stmt->execute($data);
        return (int) self::connection()->lastInsertId();
    }

    public static function update(int $id, array $attributes): bool
    {
        $data = array_intersect_key($attributes, array_flip(static::$fillable));
        $setClause = implode(', ', array_map(static fn($key) => $key . ' = :' . $key, array_keys($data)));
        $data['id'] = $id;
        $stmt = self::connection()->prepare('UPDATE ' . static::$table . ' SET ' . $setClause . ' WHERE id = :id');
        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::connection()->prepare('DELETE FROM ' . static::$table . ' WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
