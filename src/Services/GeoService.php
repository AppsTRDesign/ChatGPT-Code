<?php

declare(strict_types=1);

namespace App\Services;

final class GeoService
{
    public function detectCountryCode(string $ip): string
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'TR';
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,countryCode', false, $ctx);
        if (!$response) {
            return 'TR';
        }

        $json = json_decode($response, true);
        if (!is_array($json) || ($json['status'] ?? '') !== 'success') {
            return 'TR';
        }

        $code = strtoupper((string) ($json['countryCode'] ?? 'TR'));
        return strlen($code) === 2 ? $code : 'TR';
    }
}
