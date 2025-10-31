<?php

namespace App\Services;

use Core\Config;

class QrService
{
    public function generateUrl(string $tableUrl, array $settings): string
    {
        $config = Config::get('qr_api');
        $query = http_build_query([
            'token' => $settings['token'] ?? '',
            'type' => 'url',
            'url' => $tableUrl,
            'width' => $settings['width'] ?? 400,
            'height' => $settings['height'] ?? 400,
            'color' => ltrim($settings['color'] ?? '#000000', '#'),
            'background' => ltrim($settings['background'] ?? '#ffffff', '#'),
            'format' => $settings['format'] ?? 'png',
            'background_transparent' => !empty($settings['transparent']) ? 'true' : 'false',
            'logo' => $settings['logo'] ?? '',
        ]);

        return $config['base_url'] . '?' . $query;
    }
}
