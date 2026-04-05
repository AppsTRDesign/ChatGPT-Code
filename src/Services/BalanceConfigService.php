<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

final class BalanceConfigService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    public function get(): array
    {
        $defaults = [
            'energy_per_tick' => 300,
            'energy_base_tick_seconds' => 600,
            'energy_top_city_tick_seconds' => 360,
            'energy_nation_tier_bonus_seconds' => 12,
            'work_base_xp' => 10,
            'battle_xp_win_min' => 20,
            'battle_xp_win_max' => 35,
            'battle_xp_lose_min' => 8,
            'battle_xp_lose_max' => 16,
            'level_xp_base' => 120,
            'level_xp_curve' => 1.2,
            'level_energy_gain' => 150,
        ];

        $keys = array_keys($defaults);
        $in = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->db->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($in)");
        $stmt->execute($keys);

        $out = $defaults;
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $k = $row['key'];
            if (!array_key_exists($k, $out)) {
                continue;
            }
            $v = (string) $row['value'];
            $out[$k] = str_contains($v, '.') ? (float) $v : (int) $v;
        }

        return $out;
    }
}
