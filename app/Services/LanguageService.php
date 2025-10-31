<?php

namespace App\Services;

class LanguageService
{
    private string $directory;

    public function __construct()
    {
        $this->directory = LANG_PATH;
    }

    public function list(): array
    {
        $files = glob($this->directory . '/*.json') ?: [];
        $languages = [];
        foreach ($files as $file) {
            $code = basename($file, '.json');
            $languages[$code] = [
                'code' => $code,
                'path' => $file,
            ];
        }

        return $languages;
    }

    public function read(string $code): array
    {
        $path = $this->directory . '/' . $code . '.json';
        if (!file_exists($path)) {
            throw new \RuntimeException('Dil dosyası bulunamadı: ' . $code);
        }

        $content = file_get_contents($path);
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function save(string $code, array $translations): void
    {
        $path = $this->directory . '/' . $code . '.json';
        file_put_contents($path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function ensure(string $code, array $translations): void
    {
        $path = $this->directory . '/' . $code . '.json';
        if (!file_exists($path)) {
            $this->save($code, $translations);
            return;
        }

        $existing = json_decode(file_get_contents($path), true) ?? [];
        $merged = array_replace_recursive($existing, $translations);
        $this->save($code, $merged);
    }

    public function delete(string $code): void
    {
        $path = $this->directory . '/' . $code . '.json';
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
