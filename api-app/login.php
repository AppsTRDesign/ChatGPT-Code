<?php
require_once __DIR__ . '/../includes/helpers.php';

$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    json_response(['error' => 'invalid_input'], 422);
}

$db = get_pdo();

$stmt = $db->prepare(
    "SELECT id, password FROM users 
     WHERE email = ? AND status = 'active'"
);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['error' => 'invalid_credentials'], 401);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];

$profile = get_user_by_id((int)$user['id']);

json_response([
    'success' => true,
    'user' => $profile
]);
