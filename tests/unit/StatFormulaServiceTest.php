<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Services\StatFormulaService;

$formula = new StatFormulaService();

$cfg = [
    'level_xp_base' => 120,
    'level_xp_curve' => 1.20,
    'level_xp_multiplier' => 1.20,
    'work_xp_base' => 18,
    'work_xp_nation_multiplier' => 0.04,
    'battle_xp_win_base' => 35,
    'battle_xp_lose_base' => 10,
    'battle_xp_nation_multiplier' => 0.06,
    'city_top_work_bonus_percent' => 25,
    'city_top_energy_bonus_percent' => 40,
    'nation_energy_regen_bonus_percent' => 5,
    'nation_tier_player_weight' => 1,
    'nation_tier_city_score_weight' => 15,
    'stat_upgrade_labor_base' => 5,
    'stat_upgrade_gold_base' => 5,
    'stat_upgrade_growth_percent' => 5,
];

$checks = [
    $formula->levelXpRequirement(1, $cfg) >= 120,
    $formula->workYield(10, 10, true, 3) > 0,
    $formula->battleWinChance(10, 10, 5, 2) >= 5,
    $formula->statUpgradeCost('strength', 10)['gold'] >= 5,
];

foreach ($checks as $check) {
    if (!$check) {
        fwrite(STDERR, "StatFormulaService expectation failed\n");
        exit(1);
    }
}

echo "StatFormulaService unit test passed\n";
