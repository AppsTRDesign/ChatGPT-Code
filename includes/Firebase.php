<?php

namespace App;

class Firebase
{
    private const TOKEN_INFO_URL = 'https://oauth2.googleapis.com/tokeninfo?id_token=';

    public static function verifyIdToken(string $idToken): ?array
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            return null;
        }

        $config = Settings::firebaseConfig();
        if (!$config) {
            return null;
        }

        $projectId = $config['projectId'] ?? null;
        if (!$projectId) {
            return null;
        }

        $response = self::fetchJson(self::TOKEN_INFO_URL . urlencode($idToken));
        if (!$response || isset($response['error_description'])) {
            return null;
        }

        $audience = $response['aud'] ?? '';
        $validAudiences = array_filter([
            $projectId,
            $config['appId'] ?? null,
            $config['clientId'] ?? null,
        ]);

        if ($validAudiences && !in_array($audience, $validAudiences, true)) {
            return null;
        }

        $issuer = $response['iss'] ?? '';
        $expectedIssuer = 'https://securetoken.google.com/' . $projectId;
        if ($issuer !== '' && $issuer !== $expectedIssuer && $issuer !== 'https://accounts.google.com') {
            return null;
        }

        $firebase = $response['firebase'] ?? [];

        return [
            'uid' => $response['sub'] ?? null,
            'email' => $response['email'] ?? null,
            'email_verified' => filter_var($response['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name' => $response['name'] ?? ($response['email'] ?? null),
            'picture' => $response['picture'] ?? null,
            'provider' => $firebase['sign_in_provider'] ?? ($response['iss'] ?? 'firebase'),
        ];
    }

    private static function fetchJson(string $url): ?array
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);

        if ($content === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FAILONERROR => false,
            ]);
            $content = curl_exec($ch);
            curl_close($ch);
        }

        if ($content === false || $content === null) {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }
}
