<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;
use Core\Response;

$restaurantId = 1;
$db = Database::connection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!empty($payload['id'])) {
            $id = (int)$payload['id'];
            $status = $payload['status'] ?? 'waiting';
            $statement = $db->prepare('UPDATE waiter_calls SET status = ?, updated_at = NOW() WHERE restaurant_id = ? AND id = ?');
            $statement->execute([$status, $restaurantId, $id]);
            Response::json([
                'success' => true,
                'call' => fetchCall($db, $id),
                'message' => 'Çağrı durumu güncellendi.',
            ]);
            return;
        }

        $tableId = (int)($payload['table_id'] ?? 0);
        if ($tableId <= 0) {
            throw new InvalidArgumentException('Geçersiz masa.');
        }

        $statement = $db->prepare('INSERT INTO waiter_calls (restaurant_id, table_id, status) VALUES (?, ?, "waiting")');
        $statement->execute([$restaurantId, $tableId]);
        $id = (int)$db->lastInsertId();

        $db->prepare('UPDATE tables SET status = "occupied", updated_at = NOW() WHERE id = ? AND restaurant_id = ?')->execute([$tableId, $restaurantId]);

        Response::json([
            'success' => true,
            'call' => fetchCall($db, $id),
            'message' => 'Garson çağrısı oluşturuldu.',
        ]);
        return;
    }

    Response::json([
        'calls' => fetchCalls($db, $restaurantId),
    ]);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 400);
}

function fetchCalls(\PDO $db, int $restaurantId): array
{
    $sql = "SELECT w.id, t.name AS table_name, w.status, DATE_FORMAT(w.created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM waiter_calls w
        INNER JOIN tables t ON t.id = w.table_id
        WHERE w.restaurant_id = :restaurant
        ORDER BY w.created_at DESC LIMIT 200";
    $statement = $db->prepare($sql);
    $statement->execute([':restaurant' => $restaurantId]);

    return array_map(static function ($call) {
        $labels = [
            'waiting' => 'Beklemede',
            'on_the_way' => 'Yolda',
            'completed' => 'Tamamlandı',
        ];
        $call['table'] = $call['table_name'];
        $call['status_label'] = $labels[$call['status']] ?? $call['status'];
        unset($call['table_name']);
        return $call;
    }, $statement->fetchAll() ?: []);
}

function fetchCall(\PDO $db, int $id): array
{
    $statement = $db->prepare("SELECT w.id, w.table_id, t.name AS table_name, w.status, DATE_FORMAT(w.created_at, '%d.%m.%Y %H:%i') AS created_at FROM waiter_calls w INNER JOIN tables t ON t.id = w.table_id WHERE w.id = ?");
    $statement->execute([$id]);
    $call = $statement->fetch();
    if (!$call) {
        return [];
    }
    $labels = [
        'waiting' => 'Beklemede',
        'on_the_way' => 'Yolda',
        'completed' => 'Tamamlandı',
    ];
    $call['table'] = $call['table_name'];
    $call['status_label'] = $labels[$call['status']] ?? $call['status'];
    unset($call['table_name']);
    return $call;
}
