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
        $publicPath = str_replace(__DIR__ . '/..', '', $destination);
        Response::json([
            'success' => true,
            'path' => $publicPath,
        ]);
    }
}

Response::json([
    'success' => false,
    'message' => 'File upload failed',
], 400);
