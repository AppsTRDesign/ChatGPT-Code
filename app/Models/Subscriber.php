<?php

namespace App\Models;

class Subscriber extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM subscribers ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO subscribers (endpoint, device, browser, timezone, tags) VALUES (:endpoint, :device, :browser, :timezone, :tags)');
        return $stmt->execute([
            'endpoint' => $data['endpoint'],
            'device' => $data['device'],
            'browser' => $data['browser'],
            'timezone' => $data['timezone'],
            'tags' => $data['tags'] ?? null,
        ]);
    }

    public function findByEndpoint(string $endpoint): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subscribers WHERE endpoint = :endpoint LIMIT 1');
        $stmt->execute(['endpoint' => $endpoint]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }
}
