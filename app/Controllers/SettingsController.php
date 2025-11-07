<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Models\Setting;

final class SettingsController extends Controller
{
    public function index(): void
    {
        $settings = Setting::allAsArray();
        $defaults = [
            'telegram_api_id' => (string) Config::get('telegram.api_id', ''),
            'telegram_api_hash' => (string) Config::get('telegram.api_hash', ''),
            'rate_limit_global' => (string) Config::get('rate_limits.global', '60'),
            'rate_limit_per_phone' => (string) Config::get('rate_limits.per_phone', '30'),
            'rate_limit_per_channel' => (string) Config::get('rate_limits.per_channel', '15'),
            'mail_host' => (string) Config::get('mail.host', ''),
            'mail_port' => (string) Config::get('mail.port', ''),
            'mail_username' => (string) Config::get('mail.username', ''),
            'mail_password' => (string) Config::get('mail.password', ''),
            'mail_encryption' => (string) Config::get('mail.encryption', ''),
            'mail_from_address' => (string) Config::get('mail.from_address', ''),
            'mail_from_name' => (string) Config::get('mail.from_name', ''),
            'remote_host' => (string) Config::get('remote.host', ''),
            'remote_port' => (string) Config::get('remote.port', '22'),
            'remote_username' => (string) Config::get('remote.username', ''),
            'remote_password' => (string) Config::get('remote.password', ''),
            'branding_logo' => '',
            'branding_favicon' => '',
            'uploads_allowed_extensions' => implode(',', (array) Config::get('uploads.allowed_extensions', [])),
        ];

        $settings = array_replace($defaults, $settings);
        $this->view('admin/settings', [
            'title' => 'Genel Ayarlar',
            'settings' => $settings,
            'allowedExtensions' => $this->parseExtensions($settings['uploads_allowed_extensions'] ?? ''),
        ]);
    }

    public function update(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $pairs = [
            'telegram_api_id' => trim($_POST['telegram_api_id'] ?? ''),
            'telegram_api_hash' => trim($_POST['telegram_api_hash'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'rate_limit_global' => (string) ($_POST['rate_limit_global'] ?? ''),
            'rate_limit_per_phone' => (string) ($_POST['rate_limit_per_phone'] ?? ''),
            'rate_limit_per_channel' => (string) ($_POST['rate_limit_per_channel'] ?? ''),
            'mail_host' => trim($_POST['mail_host'] ?? ''),
            'mail_port' => trim($_POST['mail_port'] ?? ''),
            'mail_username' => trim($_POST['mail_username'] ?? ''),
            'mail_password' => trim($_POST['mail_password'] ?? ''),
            'mail_encryption' => trim($_POST['mail_encryption'] ?? ''),
            'mail_from_address' => trim($_POST['mail_from_address'] ?? ''),
            'mail_from_name' => trim($_POST['mail_from_name'] ?? ''),
            'remote_host' => trim($_POST['remote_host'] ?? ''),
            'remote_port' => trim($_POST['remote_port'] ?? ''),
            'remote_username' => trim($_POST['remote_username'] ?? ''),
            'remote_password' => trim($_POST['remote_password'] ?? ''),
        ];

        foreach ($pairs as $key => $value) {
            Setting::set($key, $value);
        }

        $extensions = $this->parseExtensions($_POST['uploads_allowed_extensions'] ?? '');
        Setting::set('uploads_allowed_extensions', implode(',', $extensions));

        $this->json([
            'status' => 'success',
            'message' => 'Ayarlar kaydedildi.',
            'reload' => true,
        ]);
    }

    public function uploadBranding(string $type): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        if (!in_array($type, ['logo', 'favicon'], true)) {
            $this->json(['status' => 'error', 'message' => 'Bilinmeyen marka türü.'], 404);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['status' => 'error', 'message' => 'Dosya alınamadı.'], 422);
        }

        $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'ico'];
        if (!in_array($extension, $allowed, true)) {
            $this->json(['status' => 'error', 'message' => 'Yalnızca görsel dosyalar yüklenebilir.'], 422);
        }

        $filename = sprintf('branding-%s-%s.%s', $type, date('YmdHis'), $extension);
        $destination = storage_path('uploads/' . $filename);
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0775, true);
        }

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
            $this->json(['status' => 'error', 'message' => 'Dosya taşınamadı.'], 500);
        }

        $settingKey = 'branding_' . $type;
        $previous = Setting::get($settingKey);
        if ($previous) {
            $previousPath = storage_path('uploads/' . $previous);
            if (is_file($previousPath)) {
                @unlink($previousPath);
            }
        }

        Setting::set($settingKey, $filename);

        $this->json([
            'status' => 'success',
            'message' => ucfirst($type) . ' güncellendi.',
            'preview' => asset('storage/uploads/' . $filename),
            'filename' => $filename,
        ]);
    }

    public function removeBranding(string $type): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        if (!in_array($type, ['logo', 'favicon'], true)) {
            $this->json(['status' => 'error', 'message' => 'Bilinmeyen marka türü.'], 404);
        }

        $settingKey = 'branding_' . $type;
        $current = Setting::get($settingKey);
        if ($current) {
            $currentPath = storage_path('uploads/' . $current);
            if (is_file($currentPath)) {
                @unlink($currentPath);
            }
        }

        Setting::set($settingKey, '');

        $this->json([
            'status' => 'success',
            'message' => ucfirst($type) . ' kaldırıldı.',
            'reload' => true,
        ]);
    }

    private function parseExtensions(string $value): array
    {
        $parts = preg_split('/[\s,]+/', strtolower($value)) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $part = trim($part, " .\t\n\r\0\x0B");
            if ($part === '') {
                continue;
            }
            $clean[$part] = true;
        }

        return array_keys($clean);
    }
}
