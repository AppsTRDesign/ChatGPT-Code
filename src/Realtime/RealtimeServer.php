<?php

namespace App\Realtime;

use PDO;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use SplObjectStorage;

class RealtimeServer implements MessageComponentInterface
{
    private PDO $pdo;
    private LoopInterface $loop;
    /** @var SplObjectStorage<ConnectionInterface, array> */
    private SplObjectStorage $clients;
    private int $lastEventId = 0;

    public function __construct(PDO $pdo, LoopInterface $loop)
    {
        $this->pdo = $pdo;
        $this->loop = $loop;
        $this->clients = new SplObjectStorage();
        $this->lastEventId = (int) $this->pdo->query('SELECT COALESCE(MAX(id), 0) FROM realtime_events')->fetchColumn();
        $this->loop->addPeriodicTimer(1.0, function (): void {
            $this->dispatchEvents();
        });
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $meta = [
            'channel' => 'files',
            'user_id' => null,
        ];
        if (property_exists($conn, 'httpRequest')) {
            $uri = $conn->httpRequest->getUri();
            $query = parse_url($uri, PHP_URL_QUERY);
            if ($query) {
                parse_str($query, $params);
                if (!empty($params['channel'])) {
                    $meta['channel'] = (string) $params['channel'];
                }
                if (isset($params['user'])) {
                    $meta['user_id'] = is_numeric($params['user']) ? (int) $params['user'] : null;
                }
            }
        }
        $this->clients->attach($conn, $meta);
        $conn->send(json_encode([
            'type' => 'ready',
            'channel' => $meta['channel'],
        ], JSON_THROW_ON_ERROR));
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $payload = json_decode((string) $msg, true);
        if (!is_array($payload)) {
            return;
        }
        $meta = $this->clients[$from] ?? ['channel' => 'files', 'user_id' => null];
        if (!empty($payload['subscribe']) && is_string($payload['subscribe'])) {
            $meta['channel'] = $payload['subscribe'];
        }
        if (array_key_exists('user_id', $payload) && $payload['user_id'] !== null) {
            $meta['user_id'] = is_numeric($payload['user_id']) ? (int) $payload['user_id'] : null;
        }
        $this->clients[$from] = $meta;
        $from->send(json_encode(['type' => 'subscribed', 'channel' => $meta['channel']], JSON_THROW_ON_ERROR));
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->clients->contains($conn)) {
            $this->clients->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }

    private function dispatchEvents(): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM realtime_events WHERE id > :last ORDER BY id ASC LIMIT 100');
        $stmt->execute([':last' => $this->lastEventId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$events) {
            return;
        }
        $maxId = $this->lastEventId;
        foreach ($events as $event) {
            $maxId = max($maxId, (int) $event['id']);
            $payload = [
                'type' => 'event',
                'channel' => $event['channel'],
                'payload' => json_decode($event['payload'] ?? '[]', true) ?: [],
                'user_id' => $event['user_id'] ? (int) $event['user_id'] : null,
                'created_at' => $event['created_at'] ?? null,
            ];
            foreach ($this->clients as $conn) {
                $meta = $this->clients[$conn] ?? ['channel' => 'files', 'user_id' => null];
                if ($meta['channel'] !== $event['channel'] && $meta['channel'] !== 'all') {
                    continue;
                }
                if ($event['user_id'] && $meta['user_id'] && (int) $event['user_id'] !== (int) $meta['user_id']) {
                    continue;
                }
                try {
                    $conn->send(json_encode($payload, JSON_THROW_ON_ERROR));
                } catch (\Throwable $e) {
                    // silently ignore
                }
            }
        }
        $this->lastEventId = $maxId;
        $cleanupStmt = $this->pdo->prepare('DELETE FROM realtime_events WHERE id <= :maxId');
        $cleanupStmt->execute([':maxId' => $maxId]);
    }
}
