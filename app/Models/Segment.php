<?php

namespace App\Models;

class Segment extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM segments ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function create(string $name, ?string $filters): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO segments (name, filters) VALUES (:name, :filters)');
        return $stmt->execute([
            'name' => $name,
            'filters' => $filters,
        ]);
    }
}
