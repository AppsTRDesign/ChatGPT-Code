<?php
class FileUploader
{
    public static function uploadLocal(array $file, array $config): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }

        $allowed = $config['allowed_extensions'] ?? [];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($allowed && !in_array($extension, $allowed, true)) {
            throw new Exception('Unsupported file type');
        }

        $maxSize = $config['max_size'] ?? (3 * 1024 * 1024);
        if (($file['size'] ?? 0) > $maxSize) {
            throw new Exception('File size exceeds limit');
        }

        $directory = rtrim($config['directory'] ?? sys_get_temp_dir(), '/');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new Exception('Upload directory is not writable');
        }

        $filename = uniqid('upload_', true) . '.' . $extension;
        $targetPath = $directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        return [
            'filename' => $filename,
            'path' => $targetPath,
            'url' => $baseUrl ? $baseUrl . '/' . $filename : $filename
        ];
    }
}
