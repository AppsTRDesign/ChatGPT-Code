<?php

declare(strict_types=1);

namespace App\Services;

final class StatFormulaService
{
    public function cityScore(array $city): int
    {
        return (int) (
            ((int) ($city['airport_level'] ?? 0) * 2)
            + ((int) ($city['industry_level'] ?? 0) * 3)
            + ((int) ($city['education_level'] ?? 0) * 2)
            + ((int) ($city['army_level'] ?? 0) * 2)
            + ((int) ($city['port_level'] ?? 0) * 2)
            + ((int) ($city['space_level'] ?? 0) * 4)
        );
    }

    public function nationTier(int $countryPlayerCount, float $avgCityScore): int
    {
        $score = ($countryPlayerCount * 0.8) + $avgCityScore;
        if ($score >= 120) {
            return 5;
        }
        if ($score >= 90) {
            return 4;
        }
        if ($score >= 65) {
            return 3;
        }
        if ($score >= 40) {
            return 2;
        }

        return 1;
    }

    public function energyTickSeconds(bool $isTopCity, int $nationTier): int
    {
        $base = $isTopCity ? 360 : 600;
        $nationBonus = max(0, $nationTier - 1) * 12;
        return max(180, $base - $nationBonus);
    }

    public function workYield(int $strength, int $education, bool $isTopCity, int $nationTier): int
    {
        $raw = 3 + ($strength * 0.22) + ($education * 0.18) + ($nationTier * 0.4);
        $multiplier = $isTopCity ? 1.25 : 1.0;
        return (int) max(1, floor($raw * $multiplier));
    }

    public function battleWinChance(int $strength, int $endurance, int $level, int $nationTier): int
    {
        $chance = 30 + ($strength * 0.8) + ($endurance * 0.7) + ($level * 0.6) + ($nationTier * 2.5);
        return (int) max(20, min(90, round($chance)));
    }

    public function battleXp(bool $won, int $nationTier): int
    {
        return $won ? random_int(20, 35) + ($nationTier * 2) : random_int(8, 16) + $nationTier;
    }

    public function workXp(int $nationTier): int
    {
        return 10 + $nationTier;
    }

    public function statUpgradeCost(string $stat, int $currentValue): array
    {
        $multiplier = match ($stat) {
            'strength' => 1.0,
            'education' => 1.1,
            'endurance' => 1.05,
            default => 1.0,
        };

        $labor = (int) ceil((4 + ($currentValue * 0.35)) * $multiplier);
        $gold = (int) ceil((4 + ($currentValue * 0.45)) * $multiplier);

        return [
            'labor_points' => max(5, $labor),
            'gold' => max(5, $gold),
        ];
    }
}
