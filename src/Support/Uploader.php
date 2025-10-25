<?php

declare(strict_types=1);

namespace App\Support;

use Exception;

class Uploader
{
    /**
     * Handle a Dropzone style upload request and return a tuple with HTTP status and payload.
     *
     * @return array{0:int,1:array}
     */
    public static function handle(string $context = 'upload'): array
    {
        if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            return [400, [
                'status' => 'error',
                'message' => 'Geçerli bir dosya bulunamadı.',
            ]];
        }

        $uploadsPath = dirname(__DIR__, 2) . '/assets/uploads';
        if (!is_dir($uploadsPath) && !mkdir($uploadsPath, 0775, true) && !is_dir($uploadsPath)) {
            return [500, [
                'status' => 'error',
                'message' => 'Yükleme klasörü oluşturulamadı.',
            ]];
        }

        $extension = strtolower((string) pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $extension = $extension ? preg_replace('/[^a-z0-9]/', '', $extension) : '';

        try {
            $random = bin2hex(random_bytes(4));
        } catch (Exception) {
            $random = substr(md5((string) microtime(true)), 0, 8);
        }

        $filename = sprintf('%s-%s-%s', $context, date('YmdHis'), $random);
        if ($extension !== '') {
            $filename .= '.' . $extension;
        }

        $target = $uploadsPath . '/' . $filename;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            return [500, [
                'status' => 'error',
                'message' => 'Dosya yüklenirken bir hata oluştu.',
            ]];
        }

        return [200, [
            'status' => 'success',
            'file' => [
                'name' => $filename,
                'url' => \asset('uploads/' . $filename),
            ],
        ]];
    }
}
