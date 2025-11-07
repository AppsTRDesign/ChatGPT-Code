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

        $id = Service::create([
            'name' => $name,
            'slug' => $slug,
            'status' => $_POST['status'] ?? 'stopped',
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

        Service::update($id, [
            'status' => $_POST['status'] ?? 'stopped',
            'last_heartbeat_at' => $_POST['last_heartbeat_at'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['status' => 'success']);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        Service::delete($id);
        $this->json(['status' => 'success']);
    }
}
