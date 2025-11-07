<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\DispatchJob;
use App\Models\MessageTemplate;

final class MessagingController extends Controller
{
    public function index(): void
    {
        $this->view('admin/messaging', [
            'title' => 'Mesaj Gönderimleri',
            'jobs' => DispatchJob::all(),
            'templates' => MessageTemplate::all(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $name = trim($_POST['name'] ?? '');
        $templateId = (int) ($_POST['template_id'] ?? 0);
        $targetType = trim($_POST['target_type'] ?? '');
        $targetValue = trim($_POST['target_value'] ?? '');
        $scheduledFor = trim($_POST['scheduled_for'] ?? '');

        if ($name === '' || $templateId <= 0 || $targetType === '' || $targetValue === '') {
            $this->json(['status' => 'error', 'message' => 'Tüm alanlar zorunludur.'], 422);
        }

        DispatchJob::create([
            'name' => $name,
            'template_id' => $templateId,
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'scheduled_for' => $scheduledFor !== '' ? $scheduledFor : null,
            'status' => 'queued',
            'created_by' => auth_user()['id'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Gönderim kuyruğa alındı.',
            'reload' => true,
        ]);
    }

    public function update(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        DispatchJob::update($id, [
            'status' => $_POST['status'] ?? 'queued',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Gönderim güncellendi.',
            'reload' => true,
        ]);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        DispatchJob::delete($id);
        $this->json([
            'status' => 'success',
            'message' => 'Gönderim silindi.',
            'reload' => true,
        ]);
    }
}
