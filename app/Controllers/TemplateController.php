<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MessageTemplate;

final class TemplateController extends Controller
{
    public function index(): void
    {
        $this->view('admin/message-templates', [
            'title' => 'Mesaj Şablonları',
            'templates' => MessageTemplate::all(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($title === '' || $body === '') {
            $this->json(['status' => 'error', 'message' => 'Başlık ve mesaj gövdesi gerekli.'], 422);
        }

        $uploadInfo = $this->handleUpload();

        MessageTemplate::create([
            'title' => $title,
            'body' => $body,
            'attachment_path' => $uploadInfo['path'] ?? null,
            'attachment_name' => $uploadInfo['name'] ?? null,
            'attachment_type' => $uploadInfo['type'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Şablon kaydedildi.',
            'reload' => true,
        ]);
    }

    public function update(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $template = MessageTemplate::find($id);
        if (!$template) {
            $this->json(['status' => 'error', 'message' => 'Şablon bulunamadı.'], 404);
        }

        $uploadInfo = $this->handleUpload($template['attachment_path']);

        MessageTemplate::update($id, [
            'title' => trim($_POST['title'] ?? $template['title']),
            'body' => trim($_POST['body'] ?? $template['body']),
            'attachment_path' => $uploadInfo['path'] ?? $template['attachment_path'],
            'attachment_name' => $uploadInfo['name'] ?? $template['attachment_name'],
            'attachment_type' => $uploadInfo['type'] ?? $template['attachment_type'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Şablon güncellendi.',
            'reload' => true,
        ]);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $template = MessageTemplate::find($id);
        if ($template && $template['attachment_path']) {
            $path = storage_path('uploads/' . $template['attachment_path']);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        MessageTemplate::delete($id);
        $this->json([
            'status' => 'success',
            'message' => 'Şablon silindi.',
            'reload' => true,
        ]);
    }

    private function handleUpload(?string $existingPath = null): array
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return [];
        }

        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'application/zip', 'application/pdf'];
        $type = mime_content_type($_FILES['file']['tmp_name']);
        if (!in_array($type, $allowed, true)) {
            $this->json(['status' => 'error', 'message' => 'Desteklenmeyen dosya türü.'], 422);
        }

        $filename = bin2hex(random_bytes(8)) . '-' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $_FILES['file']['name']);
        $destination = storage_path('uploads/' . $filename);
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0775, true);
        }
        move_uploaded_file($_FILES['file']['tmp_name'], $destination);

        if ($existingPath && $existingPath !== $filename) {
            $previous = storage_path('uploads/' . $existingPath);
            if (file_exists($previous)) {
                unlink($previous);
            }
        }

        return [
            'path' => $filename,
            'name' => $_FILES['file']['name'],
            'type' => $type,
        ];
    }
}
