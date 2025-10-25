<?php

namespace App\Services;

use GeoIp2\Database\Reader;

class GeoLocationService
{
    protected static ?Reader $reader = null;

    public static function locate(string $ipAddress): array
    {
        try {
            if (!static::$reader) {
                static::$reader = new Reader(__DIR__ . '/../../storage/geoip/GeoLite2-City.mmdb');
            }
            $record = static::$reader->city($ipAddress);
            return [
                'country' => $record->country->name,
                'city' => $record->city->name,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
            ];
        } catch (\Throwable $exception) {
            return [];
        }
    }
}
