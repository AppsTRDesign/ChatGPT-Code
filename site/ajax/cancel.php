<?php
require __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi']);
    exit;
}

$jobId = $_POST['job_id'] ?? '';
$jobId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $jobId);
if (!$jobId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'İş ID eksik']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

request_job_cancel($jobId);
mark_job_cancelled($jobId);
cleanup_job_files($jobId);

echo json_encode(['success' => true, 'message' => 'İş iptal edildi.']);
