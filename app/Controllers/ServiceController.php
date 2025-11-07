<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Service;
use App\Services\RemoteServiceManager;
use Throwable;

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

        $service = Service::find($id);
        if (!$service) {
            $this->json(['status' => 'error', 'message' => 'Servis bulunamadı.'], 404);
        }

        $statusRequested = array_key_exists('status', $_POST);
        $status = $statusRequested ? (string) $_POST['status'] : ($service['status'] ?? 'stopped');
        $data = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (array_key_exists('description', $_POST)) {
            $data['description'] = trim((string) $_POST['description']);
        }

        if (array_key_exists('command', $_POST)) {
            $data['command'] = trim((string) $_POST['command']);
        }

        $manager = new RemoteServiceManager();
        $message = 'Servis güncellendi.';

        if ($statusRequested) {
            if ($status === 'running') {
                try {
                    $pid = $manager->start($data['command'] ?? $service['command'] ?? '');
                    $data['status'] = 'running';
                    $data['last_heartbeat_at'] = date('Y-m-d H:i:s');
                    $message = 'Servis uzaktan başlatıldı. PID: ' . $pid;
                } catch (Throwable $e) {
                    $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
            } elseif ($status === 'stopped') {
                try {
                    $manager->stop($data['command'] ?? $service['command'] ?? '');
                } catch (Throwable $e) {
                    $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
                $data['status'] = 'stopped';
                $data['last_heartbeat_at'] = null;
                $message = 'Servis durduruldu.';
            } elseif ($status === 'warning') {
                $data['status'] = 'warning';
                $message = 'Servis uyarı moduna alındı.';
            } else {
                $data['status'] = $status;
            }
        } else {
            $data['status'] = $service['status'];
            $data['last_heartbeat_at'] = $service['last_heartbeat_at'];
        }

        Service::update($id, $data);

        $this->json([
            'status' => 'success',
            'message' => $message,
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
