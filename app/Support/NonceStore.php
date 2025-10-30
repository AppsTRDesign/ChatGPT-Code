<?php

declare(strict_types=1);

namespace App\Support;

final class NonceStore
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (rtrim($_ENV['CACHE_PATH'] ?? __DIR__ . '/../../storage/cache', '/') . '/nonces.json');
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function read(): array
    {
        if (!file_exists($this->path)) {
            return [];
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            return [];
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, array<string, int>> $data
     */
    private function write(array $data): void
    {
        file_put_contents($this->path, json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function has(string $clientKey, string $nonce): bool
    {
        $this->purgeExpired();
        $data = $this->read();
        return isset($data[$clientKey][$nonce]);
    }

    public function put(string $clientKey, string $nonce, int $ttlSeconds): void
    {
        $this->purgeExpired();
        $data = $this->read();
        $data[$clientKey][$nonce] = time() + $ttlSeconds;
        $this->write($data);
    }

    private function purgeExpired(): void
    {
        $now = time();
        $data = $this->read();
        $changed = false;
        foreach ($data as $key => $nonces) {
            foreach ($nonces as $nonce => $expiresAt) {
                if ($expiresAt < $now) {
                    unset($data[$key][$nonce]);
                    $changed = true;
                }
            }
            if ($data[$key] === []) {
                unset($data[$key]);
                $changed = true;
            }
        }
        if ($changed) {
            $this->write($data);
        }
    }
}
