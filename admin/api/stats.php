<?php
require_once __DIR__ . '/../auth.php';
admin_require_auth();
$pdo = admin_db();

$data = [
    'places' => (int)$pdo->query('SELECT COUNT(*) FROM places')->fetchColumn(),
    'pending_claims' => (int)$pdo->query("SELECT COUNT(*) FROM place_claim_requests WHERE status='pending'")->fetchColumn(),
    'pending_reviews' => (int)$pdo->query("SELECT COUNT(*) FROM user_reviews WHERE status='pending'")->fetchColumn(),
    'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
];
admin_json($data);
