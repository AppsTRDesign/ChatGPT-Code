<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\TelegramAccount;

final class PhoneController extends Controller
{
    public function index(): void
    {
        $this->view('admin/phones', [
            'title' => 'Kayıtlı Telefonlar',
            'accounts' => TelegramAccount::all(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $phone = trim($_POST['phone_number'] ?? '');
        if ($phone === '') {
            $this->json(['status' => 'error', 'message' => 'Telefon numarası gerekli.'], 422);
        }

        $exists = TelegramAccount::findByPhone($phone);
        if ($exists) {
            $this->json(['status' => 'error', 'message' => 'Telefon numarası zaten kayıtlı.'], 409);
        }

        $id = TelegramAccount::create([
            'phone_number' => $phone,
            'label' => trim($_POST['label'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'session_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['status' => 'success', 'id' => $id]);
    }

    public function update(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $account = TelegramAccount::find($id);
        if (!$account) {
            $this->json(['status' => 'error', 'message' => 'Kayıt bulunamadı.'], 404);
        }

        TelegramAccount::update($id, [
            'label' => trim($_POST['label'] ?? $account['label']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'session_status' => $_POST['session_status'] ?? $account['session_status'],
            'banned_at' => $_POST['banned_at'] ?? $account['banned_at'],
            'last_seen_at' => $_POST['last_seen_at'] ?? $account['last_seen_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['status' => 'success']);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        TelegramAccount::delete($id);
        $this->json(['status' => 'success']);
    }
}
