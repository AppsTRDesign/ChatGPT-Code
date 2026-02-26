<?php
require_once __DIR__ . '/_init.php';

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$current = (string) ($_POST['current_password'] ?? '');
$new = (string) ($_POST['new_password'] ?? '');
if (strlen($new) < 8) {
    json_response(false, 'Yeni şifre en az 8 karakter olmalı');
}

$stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $adminId]);
$hash = (string) $stmt->fetchColumn();
if ($hash === '' || !password_verify($current, $hash)) {
    json_response(false, 'Mevcut şifre yanlış');
}

db()->prepare('UPDATE admins SET password_hash = :h WHERE id = :id')->execute(['h' => password_hash($new, PASSWORD_DEFAULT), 'id' => $adminId]);
json_response(true, 'Şifre güncellendi');
