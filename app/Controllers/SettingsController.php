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
        ];

        $settings = array_replace($defaults, $settings);
        $this->view('admin/settings', [
            'title' => 'Genel Ayarlar',
            'settings' => $settings,
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

        if (isset($_FILES['branding']) && is_array($_FILES['branding']['tmp_name'])) {
            foreach ($_FILES['branding']['tmp_name'] as $index => $tmpName) {
                if ($tmpName === '') {
                    continue;
                }
                $type = mime_content_type($tmpName);
                if (!in_array($type, ['image/svg+xml', 'image/png', 'image/x-icon'], true)) {
                    continue;
                }

                $filename = $index === 0 ? 'logo.svg' : 'favicon.svg';
                $destination = storage_path('uploads/' . $filename);
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0775, true);
                }
                move_uploaded_file($tmpName, $destination);
                Setting::set($filename === 'logo.svg' ? 'branding_logo' : 'branding_favicon', $filename);
            }
        }

        $this->json([
            'status' => 'success',
            'message' => 'Ayarlar kaydedildi.',
            'reload' => true,
        ]);
    }
}
