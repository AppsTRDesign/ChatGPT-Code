<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use Throwable;

final class Service extends Model
{
    protected static string $table = 'services';
    protected static array $fillable = [
        'name',
        'slug',
        'status',
        'description',
        'command',
        'last_heartbeat_at',
        'created_at',
        'updated_at',
    ];

    public static function findBySlug(string $slug): ?array
    {
        try {
            $stmt = self::connection()->prepare('SELECT * FROM ' . self::tableName() . ' WHERE `slug` = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function ensureDefaults(): void
    {
        $defaults = Config::get('services', []);
        if (!is_array($defaults)) {
            return;
        }

        foreach ($defaults as $slug => $service) {
            if (!is_array($service)) {
                continue;
            }

            $existing = self::findBySlug((string) $slug);
            $payload = [
                'name' => $service['name'] ?? ucfirst(str_replace('-', ' ', (string) $slug)),
                'slug' => (string) $slug,
                'status' => $service['status'] ?? 'stopped',
                'description' => $service['description'] ?? '',
                'command' => $service['command'] ?? '',
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $updates = [];
                foreach (['name', 'description', 'command'] as $field) {
                    if ($payload[$field] !== '' && $existing[$field] !== $payload[$field]) {
                        $updates[$field] = $payload[$field];
                    }
                }

                if ($updates !== []) {
                    $updates['updated_at'] = $payload['updated_at'];
                    try {
                        self::update((int) $existing['id'], $updates);
                    } catch (Throwable $e) {
                        continue;
                    }
                }
                continue;
            }

            $payload['created_at'] = $payload['updated_at'];
            $payload['last_heartbeat_at'] = null;
            try {
                self::create($payload);
            } catch (Throwable $e) {
                continue;
            }
        }
    }
}
