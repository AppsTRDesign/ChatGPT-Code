<?php

namespace App\Services;

use Core\Config;

class QrService
{
    public function generateUrl(string $tableUrl, array $settings): string
    {
        $config = Config::get('qr_api');
        $logo = $settings['logo_url'] ?? ($settings['logo'] ?? '');
        if ($logo && !preg_match('/^https?:\/\//i', $logo)) {
            if (str_starts_with($logo, '/')) {
                $logo = ltrim($logo, '/');
            }
            $logo = rtrim(BASE_URL, '/') . '/' . $logo;
        }

        $transparent = $settings['transparent'] ?? false;
        if (is_string($transparent)) {
            $transparent = filter_var($transparent, FILTER_VALIDATE_BOOLEAN);
        }

        $query = http_build_query([
            'token' => $settings['token'] ?? '',
            'type' => 'url',
            'url' => $tableUrl,
            'width' => $settings['width'] ?? 400,
            'height' => $settings['height'] ?? 400,
            'color' => ltrim($settings['color'] ?? '#000000', '#'),
            'background' => ltrim($settings['background'] ?? '#ffffff', '#'),
            'format' => $settings['format'] ?? 'png',
            'background_transparent' => $transparent ? 'true' : 'false',
            'logo_url' => $logo,
        ]);

        return $config['base_url'] . '?' . $query;
    }
}
