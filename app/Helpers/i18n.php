<?php

declare(strict_types=1);

function app_lang(): string
{
    $lang = (string) ($_GET['lang'] ?? $_SERVER['HTTP_X_LANG'] ?? $_SESSION['lang'] ?? 'tr');
    $lang = strtolower(trim($lang));
    return in_array($lang, ['tr', 'en'], true) ? $lang : 'tr';
}

function app_i18n_map(?string $lang = null): array
{
    static $cache = [];
    $lang ??= app_lang();
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }

    $path = dirname(__DIR__, 2) . '/lang/' . $lang . '.json';
    if (!file_exists($path)) {
        $cache[$lang] = [];
        return $cache[$lang];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    $cache[$lang] = is_array($decoded) ? $decoded : [];
    return $cache[$lang];
}

function tr(string $key, array $vars = [], ?string $lang = null): string
{
    $segments = explode('.', $key);
    $value = app_i18n_map($lang);
    foreach ($segments as $seg) {
        if (!is_array($value) || !array_key_exists($seg, $value)) {
            return $key;
        }
        $value = $value[$seg];
    }

    $text = is_string($value) ? $value : $key;
    foreach ($vars as $name => $replacement) {
        $text = str_replace('{' . $name . '}', (string) $replacement, $text);
    }
    return $text;
}
