<?php
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_token(string $provided): void
{
    if (!API_TOKEN) {
        return; // token enforcement disabled
    }
    if (!$provided || !hash_equals(API_TOKEN, $provided)) {
        json_response(['error' => 'unauthorized'], 401);
    }
}
