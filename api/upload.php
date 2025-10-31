<?php

require_once __DIR__ . '/../bootstrap.php';

use Core\Config;
use Core\Response;

$uploadPath = Config::get('storage')['uploads'];
if (!is_dir($uploadPath)) {
    mkdir($uploadPath, 0775, true);
}

if (!empty($_FILES['file'])) {
    $file = $_FILES['file'];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('upload_', true) . '.' . $extension;
    $destination = $uploadPath . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $relativePath = 'storage/uploads/' . $filename;
        $publicUrl = rtrim(BASE_URL, '/') . '/' . ltrim($relativePath, '/');

        Response::json([
            'success' => true,
            'path' => $relativePath,
            'url' => $publicUrl,
            'message' => 'Dosya başarıyla yüklendi.',
        ]);
    }
}

Response::json([
    'success' => false,
    'message' => 'Dosya yüklenirken sorun oluştu.',
], 400);
