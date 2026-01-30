<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

$provider = $input['provider'] ?? '';
$idToken  = $input['id_token'] ?? '';
$name     = trim($input['name'] ?? 'Google User');
$email    = trim($input['email'] ?? '');

if ($provider !== 'google' || !$idToken) {
    json_response(['success' => false, 'error' => 'invalid_provider'], 400);
}

/* 🔐 Google Token doğrula */
$client = new Google_Client([
    'client_id' => "1063276280060-ufkg168easlt949km6r6bk7s3u6bg8af.apps.googleusercontent.com" // WEB CLIENT ID
]);

$payload = $client->verifyIdToken($idToken);
if (!$payload) {
    json_response(['success' => false, 'error' => 'invalid_google_token'], 401);
}

$googleId = $payload['sub'];
$photoUrl = $payload['picture'] ?? null;

$db = get_pdo();

/* 🔍 Kullanıcıyı bul */
$stmt = $db->prepare("
    SELECT id, profile_photo 
    FROM users 
    WHERE google_id = ? OR email = ?
    LIMIT 1
");
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* 📸 Varsayılan foto */
$finalPhoto = null;

/* 🆕 Kullanıcı YOKSA */
if (!$user) {

    if ($photoUrl) {
        $ROOT = dirname(__DIR__);
        $processed = process_profile_photo(
            $name,
            $photoUrl,
            $ROOT . '/uploads/tmp/',
            $ROOT . '/uploads/user/google-user/profile/'
        );
        if (!empty($processed['url'])) {
            $finalPhoto = $processed['url'];
        }
    }

    $stmt = $db->prepare("
        INSERT INTO users 
        (name, email, google_id, auth_provider, profile_photo, status, created_at)
        VALUES (?, ?, ?, 'google', ?, 'active', NOW())
    ");
    $stmt->execute([
        $name,
        $email,
        $googleId,
        $finalPhoto
    ]);

    $userId = (int)$db->lastInsertId();

} else {

    $userId = (int)$user['id'];

    /* 📸 SADECE foto yoksa Google foto ekle */
    if (
        (empty($user['profile_photo']) || str_contains($user['profile_photo'], 'default-user'))
        && $photoUrl
    ) {
        $ROOT = dirname(__DIR__);
        $processed = process_profile_photo(
            $name,
            $photoUrl,
            $ROOT . '/uploads/tmp/',
            $ROOT . '/uploads/user/google-user/profile/'
        );
        if (!empty($processed['url'])) {
            $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$processed['url'], $userId]);
        }
    }
}

/* 🔐 Session aç (web + android uyumlu) */
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;

/* ✅ TEK KAYNAK: get_user_by_id */
$profile = get_user_by_id($userId);

json_response([
    'success' => true,
    'user' => $profile
]);
