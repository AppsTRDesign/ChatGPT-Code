<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Firebase;
use App\Settings;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Yalnızca POST isteklerine izin verilir.']);
    exit;
}

if (!Settings::firebaseEnabled()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Firebase entegrasyonu pasif.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$idToken = trim((string) ($input['idToken'] ?? ''));
$provider = strtolower(trim((string) ($input['provider'] ?? '')));

if ($idToken === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Kimlik belirteci alınamadı.']);
    exit;
}

$providers = Settings::firebaseProviders();
if ($providers && !in_array($provider, $providers, true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Bu sağlayıcı devre dışı bırakıldı.']);
    exit;
}

$verified = Firebase::verifyIdToken($idToken);
if (!$verified) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Kimlik doğrulaması doğrulanamadı.']);
    exit;
}

if ($provider === '' && !empty($verified['provider'])) {
    $provider = strtolower((string) $verified['provider']);
}

try {
    $loginResult = Auth::loginWithFirebase($verified, $provider ?: 'firebase');
} catch (\RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

if (!$loginResult) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sosyal giriş sırasında bir hata oluştu.']);
    exit;
}

$redirect = '/client/dashboard';
$user = Auth::user();
if ($user && $user['role'] === 'admin') {
    $redirect = '/admin/dashboard';
}

echo json_encode([
    'status' => 'ok',
    'redirect' => $redirect,
]);
