<?php

namespace App;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Exception\GeoIp2Exception;
use GeoIp2\Exception\InvalidDatabaseException;

class Geo
{
    private static bool $initialised = false;
    private static bool $available = false;
    private static ?Reader $reader = null;

    private static function databasePath(): ?string
    {
        $candidates = [];
        $configured = Settings::get('geo_database_path');
        if ($configured) {
            $candidates[] = $configured;
        }
        $candidates[] = __DIR__ . '/geo/GeoLite2-City.mmdb';

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            $resolved = self::resolveCandidate($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private static function resolveCandidate(string $candidate): ?string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return null;
        }

        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }

        $real = realpath($candidate);
        if ($real && is_file($real) && is_readable($real)) {
            return $real;
        }

        if (is_dir($candidate)) {
            $path = rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'GeoLite2-City.mmdb';
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        if ($real && is_dir($real)) {
            $path = rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'GeoLite2-City.mmdb';
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function ensureReader(): void
    {
        if (self::$initialised) {
            return;
        }
        self::$initialised = true;

        if (!class_exists(Reader::class)) {
            return;
        }

        $path = self::databasePath();
        if (!$path) {
            return;
        }

        try {
            self::$reader = new Reader($path);
            self::$available = true;
        } catch (InvalidDatabaseException|\RuntimeException $exception) {
            error_log('Geo database load failed: ' . $exception->getMessage());
            self::$reader = null;
            self::$available = false;
        }
    }

    public static function lookup(?string $ip): array
    {
        $ip = $ip ? trim($ip) : '';
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return [];
        }

        self::ensureReader();

        if (!self::$available || !self::$reader) {
            return [];
        }

        try {
            $record = self::$reader->city($ip);
            return [
                'country' => $record->country->name ?: $record->country->isoCode,
                'city' => $record->city->name ?: null,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
            ];
        } catch (AddressNotFoundException) {
            return [];
        } catch (GeoIp2Exception|\RuntimeException $exception) {
            error_log('Geo lookup failed: ' . $exception->getMessage());
            return [];
        }
    }
}
