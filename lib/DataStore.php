<?php

class DataStore
{
    private string $filePath;
    private array $data;

    public function __construct(string $filePath, array $default = [])
    {
        $this->filePath = $filePath;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->data = $default;
        } else {
            $content = file_get_contents($filePath);
            $this->data = $content ? json_decode($content, true) ?? $default : $default;
        }
    }

    public function all(): array
    {
        return $this->data;
    }

    public function save(array $data): void
    {
        $this->data = $data;
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function update(callable $callback): array
    {
        $data = $callback($this->data);
        $this->save($data);
        return $data;
    }
}
