<?php

namespace App;

use RuntimeException;

class QrHistory
{
    private const VALID_FORMATS = ['png', 'jpg', 'svg'];

    /**
     * @param array<string, mixed> $options
     * @param array<string, string> $assets
     * @param array<string, mixed> $meta
     * @return array{id:int,files:array<int,array<string,mixed>>,preview_data:string|null}
     */
    public static function record(int $userId, string $origin, string $type, string $content, array $options, array $assets, array $meta = []): array
    {
        if (!in_array($origin, ['client', 'api'], true)) {
            $origin = 'client';
        }

        $storageDir = dirname(__DIR__) . '/uploads/qr/' . $userId;
        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
                throw new RuntimeException('QR kayıt dizini oluşturulamadı.');
            }
        }

        $basename = date('YmdHis') . '_' . bin2hex(random_bytes(6));
        $files = [];
        $previewData = null;

        foreach ($assets as $format => $binary) {
            $format = strtolower((string) $format);
            if (!in_array($format, self::VALID_FORMATS, true)) {
                continue;
            }

            $filename = $basename . '.' . $format;
            $absolutePath = $storageDir . '/' . $filename;
            if (@file_put_contents($absolutePath, $binary) === false) {
                throw new RuntimeException('QR çıktısı kaydedilemedi.');
            }

            $mime = self::mimeForFormat($format);
            $relativePath = '/uploads/qr/' . $userId . '/' . $filename;
            $size = @filesize($absolutePath) ?: null;

            if ($previewData === null && $mime) {
                $encoded = base64_encode(is_string($binary) ? $binary : (string) $binary);
                $previewData = 'data:' . $mime . ';base64,' . $encoded;
            }

            $files[] = [
                'format' => $format,
                'mime' => $mime,
                'filename' => $filename,
                'path' => $relativePath,
                'size' => $size,
            ];
        }

        if (!$files) {
            throw new RuntimeException('Desteklenen çıktı formatı bulunamadı.');
        }

        $db = Helpers::db();
        $stmt = $db->prepare('INSERT INTO qr_codes (user_id, origin, type, content, options_json, files_json, meta_json) VALUES (:user_id, :origin, :type, :content, :options, :files, :meta)');
        $stmt->execute([
            'user_id' => $userId,
            'origin' => $origin,
            'type' => mb_strimwidth($type, 0, 60),
            'content' => $content,
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'files' => json_encode($files, JSON_UNESCAPED_UNICODE),
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);

        $id = (int) $db->lastInsertId();

        return [
            'id' => $id,
            'files' => $files,
            'preview_data' => $previewData,
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Helpers::db()->prepare('SELECT * FROM qr_codes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['files'] = json_decode($row['files_json'] ?? '[]', true) ?: [];
        $row['options'] = json_decode($row['options_json'] ?? '[]', true) ?: [];
        $row['meta'] = json_decode($row['meta_json'] ?? '[]', true) ?: [];
        return $row;
    }

    public static function deleteForUser(int $id, int $userId): bool
    {
        $record = self::find($id);
        if (!$record || (int) $record['user_id'] !== $userId) {
            return false;
        }

        self::deleteFiles($record);
        $stmt = Helpers::db()->prepare('DELETE FROM qr_codes WHERE id = :id AND user_id = :user_id');
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public static function deleteByAdmin(int $id): bool
    {
        $record = self::find($id);
        if (!$record) {
            return false;
        }

        self::deleteFiles($record);
        $stmt = Helpers::db()->prepare('DELETE FROM qr_codes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function fileForDownload(int $id, string $format, ?int $userId = null): ?array
    {
        $record = self::find($id);
        if (!$record) {
            return null;
        }

        if ($userId !== null && (int) $record['user_id'] !== $userId) {
            return null;
        }

        foreach ($record['files'] as $file) {
            if (($file['format'] ?? null) === strtolower($format)) {
                $absolute = dirname(__DIR__) . '/uploads/qr/' . $record['user_id'] . '/' . $file['filename'];
                if (!is_file($absolute)) {
                    continue;
                }
                return [
                    'path' => $absolute,
                    'mime' => $file['mime'] ?? self::mimeForFormat($format),
                    'download_name' => 'qr-code-' . $id . '.' . $file['format'],
                ];
            }
        }

        return null;
    }

    private static function deleteFiles(array $record): void
    {
        $files = $record['files'] ?? [];
        foreach ($files as $file) {
            if (empty($file['filename'])) {
                continue;
            }
            $path = dirname(__DIR__) . '/uploads/qr/' . $record['user_id'] . '/' . $file['filename'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private static function mimeForFormat(string $format): string
    {
        return match ($format) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }
}
