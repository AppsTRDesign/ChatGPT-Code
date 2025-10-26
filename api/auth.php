<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz güvenlik belirteci.']);
    exit;
}

try {
    switch ($action) {
        case 'login':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $user = find_user_by_email($pdo, $email);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new RuntimeException('E-posta veya şifre hatalı.');
            }
            $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')->execute([':id' => $user['id']]);
            login_user($user);
            echo json_encode([
                'status' => 'success',
                'message' => 'Hoş geldiniz ' . $user['name'] . '!',
                'redirect' => is_admin() ? BASE_URL . '/admin' : BASE_URL . '/client',
            ]);
            break;

        case 'register':
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $packageId = isset($_POST['package_id']) ? (int) $_POST['package_id'] : null;

            if (strlen($name) < 3) {
                throw new RuntimeException('Ad soyad en az 3 karakter olmalı.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta girin.');
            }
            if (strlen($password) < 8) {
                throw new RuntimeException('Şifre en az 8 karakter olmalı.');
            }
            if (find_user_by_email($pdo, $email)) {
                throw new RuntimeException('Bu e-posta zaten kayıtlı.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, package_id) VALUES (:name, :email, :password, :role, :package)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hash,
                ':role' => 'client',
                ':package' => $packageId ?: null,
            ]);
            $userId = (int) $pdo->lastInsertId();
            $user = find_user_by_email($pdo, $email);
            login_user($user);
            echo json_encode([
                'status' => 'success',
                'message' => 'Kayıt tamamlandı.',
                'redirect' => BASE_URL . '/client',
                'user_id' => $userId,
            ]);
            break;

        case 'forgot':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $user = find_user_by_email($pdo, $email);
            if (!$user) {
                throw new RuntimeException('E-posta bulunamadı.');
            }
            $token = bin2hex(random_bytes(32));
            $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires)');
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':expires' => $expires,
            ]);
            echo json_encode([
                'status' => 'success',
                'message' => 'Şifre sıfırlama bağlantısı e-postanıza gönderildi (örnek).',
                'token' => $token,
            ]);
            break;

        case 'reset':
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 8) {
                throw new RuntimeException('Şifre en az 8 karakter olmalı.');
            }
            $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1');
            $stmt->execute([':token' => $token]);
            $reset = $stmt->fetch();
            if (!$reset) {
                throw new RuntimeException('Token geçersiz veya süresi dolmuş.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = :password WHERE id = :id')->execute([
                ':password' => $hash,
                ':id' => $reset['user_id'],
            ]);
            $pdo->prepare('DELETE FROM password_resets WHERE id = :id')->execute([':id' => $reset['id']]);
            echo json_encode([
                'status' => 'success',
                'message' => 'Şifreniz güncellendi.',
                'redirect' => BASE_URL . '/login',
            ]);
            break;

        case 'logout':
            logout_user();
            echo json_encode(['status' => 'success', 'redirect' => BASE_URL . '/login']);
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
