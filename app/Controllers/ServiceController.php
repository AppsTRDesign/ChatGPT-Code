<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Service;

final class ServiceController extends Controller
{
    public function index(): void
    {
        $this->view('admin/services', [
            'title' => 'Servis Kontrolleri',
            'services' => Service::all(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($name === '' || $slug === '') {
            $this->json(['status' => 'error', 'message' => 'Servis adı ve anahtarı gerekli.'], 422);
        }

        if (Service::findBySlug($slug)) {
            $this->json(['status' => 'error', 'message' => 'Bu servis anahtarı zaten kullanımda.'], 409);
        }

        $id = Service::create([
            'name' => $name,
            'slug' => $slug,
            'status' => $_POST['status'] ?? 'stopped',
            'description' => trim($_POST['description'] ?? ''),
            'command' => trim($_POST['command'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'id' => $id,
            'message' => 'Servis kaydedildi.',
            'reload' => true,
        ]);
    }

    public function update(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $data = [
            'status' => $_POST['status'] ?? 'stopped',
            'last_heartbeat_at' => $_POST['last_heartbeat_at'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!isset($_POST['last_heartbeat_at'])) {
            if ($data['status'] === 'running') {
                $data['last_heartbeat_at'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] === 'stopped') {
                $data['last_heartbeat_at'] = null;
            }
        }

        if (array_key_exists('description', $_POST)) {
            $data['description'] = trim((string) $_POST['description']);
        }

        if (array_key_exists('command', $_POST)) {
            $data['command'] = trim((string) $_POST['command']);
        }

        Service::update($id, $data);

        $this->json([
            'status' => 'success',
            'message' => 'Servis güncellendi.',
            'reload' => true,
        ]);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        Service::delete($id);
        $this->json([
            'status' => 'success',
            'message' => 'Servis silindi.',
            'reload' => true,
        ]);
    }
}
