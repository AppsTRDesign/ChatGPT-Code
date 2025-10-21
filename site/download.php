<?php
require __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$jobId = $_GET['job'] ?? '';
$jobId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId);
$file = resolve_download_token($token);
if (!$file) {
    http_response_code(404);
    echo 'Dosya bulunamadı veya token geçersiz.';
    exit;
}

$filename = basename($file);
$filesize = filesize($file);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $filesize);
readfile($file);

cleanup_storage_file($file);
if ($jobId) {
    $jobFile = job_file($jobId);
    if (is_file($jobFile)) {
        cleanup_job_files($jobId, true, false);
        update_job($jobId, [
            'status' => 'downloaded',
            'message' => 'Dosya indirildi.',
            'downloaded_at' => date('c')
        ]);
        finalize_job($jobId);
    }
}

exit;
