<?php

namespace App\Services;

use Core\Config;

class SettingsService
{
    private string $settingsPath;

    public function __construct()
    {
        $this->settingsPath = Config::get('storage')['settings'];
        if (!file_exists($this->settingsPath)) {
            file_put_contents($this->settingsPath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function all(): array
    {
        $content = file_get_contents($this->settingsPath);
        return json_decode($content, true) ?? [];
    }

    public function update(array $data): array
    {
        $settings = array_replace_recursive($this->all(), $data);
        file_put_contents($this->settingsPath, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $settings;
    }
}
