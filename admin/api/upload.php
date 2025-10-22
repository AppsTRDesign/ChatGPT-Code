<?php
require_once __DIR__ . '/../../lib/helpers.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (empty($_FILES['file'])) {
    json_response(['error' => 'Yüklenecek dosya bulunamadı.'], 422);
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Dosya yüklenirken hata oluştu.'], 500);
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed, true)) {
    json_response(['error' => 'Sadece JPEG, PNG veya WEBP dosyaları yükleyebilirsiniz.'], 422);
}

$uploadsPath = ensure_uploads_path();
$filename = uniqid('menu_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$target = $uploadsPath . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    json_response(['error' => 'Dosya kaydedilemedi.'], 500);
}

$publicPath = str_replace(__DIR__ . '/../../', '', $target);
$publicUrl = rtrim(config('base_url'), '/') . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $publicPath);

json_response(['success' => true, 'path' => $target, 'url' => $publicUrl]);
