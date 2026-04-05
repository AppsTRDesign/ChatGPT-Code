<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

final class GameService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    public function bootPlayer(int $playerId = 1): void
    {
        $stmt = $this->db->prepare('SELECT id FROM players WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $playerId]);

        if (!$stmt->fetch()) {
            $insert = $this->db->prepare('INSERT INTO players (id, nation_name, treasury, population, soldiers, influence, updated_at) VALUES (:id, :nation, :treasury, :population, :soldiers, :influence, NOW())');
            $insert->execute([
                'id' => $playerId,
                'nation' => 'Noa Republic',
                'treasury' => 10000,
                'population' => 5000,
                'soldiers' => 250,
                'influence' => 50,
            ]);
        }
    }

    public function state(int $playerId = 1): array
    {
        $this->bootPlayer($playerId);

        $stmt = $this->db->prepare('SELECT id, nation_name, treasury, population, soldiers, influence, updated_at FROM players WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $playerId]);

        $player = $stmt->fetch() ?: [];

        return [
            'player' => $player,
            'meta' => [
                'server_time' => gmdate(DATE_ATOM),
                'tick_interval_seconds' => 15,
            ],
        ];
    }

    public function trainArmy(int $amount, int $playerId = 1): array
    {
        $amount = max(1, min($amount, 250));
        $costPerUnit = 20;
        $cost = $amount * $costPerUnit;

        $player = $this->state($playerId)['player'];

        if ((int) $player['treasury'] < $cost) {
            return ['ok' => false, 'message' => 'Yetersiz hazine.'];
        }

        $stmt = $this->db->prepare('UPDATE players SET treasury = treasury - :cost, soldiers = soldiers + :amount, influence = influence + 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'cost' => $cost,
            'amount' => $amount,
            'id' => $playerId,
        ]);

        return ['ok' => true, 'message' => $amount . ' asker eğitildi.'];
    }

    public function collectTaxes(int $playerId = 1): array
    {
        $player = $this->state($playerId)['player'];
        $gain = max(100, (int) floor(((int) $player['population']) * 0.08));

        $stmt = $this->db->prepare('UPDATE players SET treasury = treasury + :gain, influence = influence + 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'gain' => $gain,
            'id' => $playerId,
        ]);

        return ['ok' => true, 'message' => 'Vergiler toplandı: +' . $gain . '₺'];
    }

    public function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $stmt = $this->db->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
            $stmt->execute([
                'key' => $key,
                'value' => (string) $value,
            ]);
        }
    }

    public function getSettings(): array
    {
        $stmt = $this->db->query('SELECT `key`, `value` FROM settings');
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return $settings;
    }
}
