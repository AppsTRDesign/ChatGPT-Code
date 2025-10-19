<?php
require __DIR__ . '/../includes/functions.php';

$jobId = $_GET['job_id'] ?? '';
$jobId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId);
if (!$jobId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz iş ID']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$job = read_job($jobId);

if (!empty($job['download_file']) && empty($job['download_url'])) {
    $downloadPath = NS_OUTPUT_PATH . '/' . basename($job['download_file']);
    if (file_exists($downloadPath)) {
        $job['download_url'] = '/download.php?token=' . urlencode(build_download_token($downloadPath));
    }
}

echo json_encode($job, JSON_UNESCAPED_UNICODE);
