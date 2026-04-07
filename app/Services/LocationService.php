<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\PlayerModel;

final class LocationService
{
    public function __construct(
        private readonly GeoService $geoService = new GeoService(),
        private readonly PlayerModel $playerModel = new PlayerModel()
    ) {
    }

    public function assignPlayerLocation(int $userId): array
    {
        $existing = $this->playerModel->me($userId);
        if ($existing) {
            return [
                'country_id' => (int) $existing['current_country_id'],
                'region_id' => (int) $existing['current_region_id'],
                'source' => 'existing',
            ];
        }

        $geoCfg = config('geo');
        $fallbackCountryId = (int) ($geoCfg['fallback_country_id'] ?? 1);

        $ip = $this->geoService->resolveClientIp();
        $geo = $this->geoService->detectFromIP($ip)
            ?? $this->geoService->detectFromMaxMind($ip)
            ?? ['country_code' => null, 'city' => null, 'lat' => null, 'lng' => null, 'source' => 'fallback'];

        $country = $this->geoService->resolveCountry($geo['country_code'] ?? null);
        $countryId = (int) ($country['id'] ?? $fallbackCountryId);
        $region = $this->geoService->resolveRegion(
            $countryId,
            $geo['city'] ?? null,
            isset($geo['lat']) ? (float) $geo['lat'] : null,
            isset($geo['lng']) ? (float) $geo['lng'] : null
        );

        if (!$region) {
            $region = $this->geoService->resolveRegion($fallbackCountryId, null, null, null);
        }

        $regionId = (int) ($region['id'] ?? 0);
        $countryId = (int) ($region['country_id'] ?? $countryId);

        $this->playerModel->createProfile($userId, $regionId, $countryId);
        $this->playerModel->createCitizenship($userId, $countryId);
        $this->updatePopulation($regionId, 1);

        return ['country_id' => $countryId, 'region_id' => $regionId, 'source' => (string) ($geo['source'] ?? 'fallback')];
    }

    public function updatePopulation(int $regionId, int $delta): void
    {
        $stmt = Database::connection()->prepare('UPDATE regions SET population = GREATEST(population + :delta, 0) WHERE id = :id');
        $stmt->execute(['delta' => $delta, 'id' => $regionId]);
    }
}
