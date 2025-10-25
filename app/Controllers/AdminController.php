<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ApiKey;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;

class AdminController extends Controller
{
    public function dashboard(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $stats = DashboardService::getAdminStats();
        $daily = ReportService::adminDailySummary();
        $recent = Notification::recent(8);

        return $this->view('admin/dashboard/index', [
            'title' => 'Yönetim Paneli',
            'stats' => $stats,
            'daily' => $daily,
            'recent' => $recent,
        ]);
    }

    public function clients(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/clients/index', [
            'title' => 'Müşteri Yönetimi'
        ]);
    }

    public function clientsData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(Client::allWithStats());
    }

    public function storeClient(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $name = trim((string) ($payload['name'] ?? ''));
        $domain = trim((string) ($payload['domain'] ?? ''));
        $status = $payload['status'] ?? 'active';

        if ($username === '' || $password === '' || $name === '' || $domain === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Tüm alanlar zorunludur'
            ], 422);
        }

        try {
            $userId = User::create([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => 'client'
            ]);

            Client::create([
                'user_id' => $userId,
                'name' => $name,
                'domain' => $domain,
                'status' => $status
            ]);
        } catch (\Throwable $exception) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Müşteri oluşturulamadı: ' . $exception->getMessage()
            ], 400);
        }

        return $this->jsonResponse([
            'status' => 'success'
        ]);
    }

    public function updateClient(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $clientId = (int) ($payload['id'] ?? 0);
        if (!$clientId) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz müşteri'
            ], 422);
        }

        $client = Client::find($clientId);
        if (!$client) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Müşteri bulunamadı'
            ], 404);
        }

        $updateClient = array_filter([
            'name' => $payload['name'] ?? null,
            'domain' => $payload['domain'] ?? null,
            'status' => $payload['status'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($updateClient) {
            Client::updateById($clientId, $updateClient);
        }

        $updateUser = [];
        if (!empty($payload['password'])) {
            $updateUser['password'] = password_hash((string) $payload['password'], PASSWORD_BCRYPT);
        }
        if (!empty($payload['username'])) {
            $updateUser['username'] = $payload['username'];
        }

        if ($updateUser) {
            User::updateById((int) $client['user_id'], $updateUser);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function deleteClient(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $clientId = (int) $this->request->input('id', 0);
        if (!$clientId) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz müşteri'
            ], 422);
        }

        if ($client = Client::find($clientId)) {
            Client::deleteById($clientId);
            User::deleteById((int) $client['user_id']);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function apiKeys(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/api/index', [
            'title' => 'API Anahtarları',
            'clients' => Client::all()
        ]);
    }

    public function apiKeysData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(ApiKey::all());
    }

    public function storeApiKey(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $clientId = (int) ($payload['client_id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        $permissions = $payload['permissions'] ?? [];

        if (!$clientId || $name === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Müşteri ve ad zorunludur'
            ], 422);
        }

        $apiKeyId = ApiKey::createForClient($clientId, [
            'name' => $name,
            'permissions' => $permissions,
            'status' => $payload['status'] ?? 'active'
        ]);

        $apiKey = ApiKey::find($apiKeyId);

        return $this->jsonResponse([
            'status' => 'success',
            'api_key' => $apiKey
        ]);
    }

    public function updateApiKey(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $id = (int) ($payload['id'] ?? 0);
        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz anahtar'
            ], 422);
        }

        $update = array_filter([
            'name' => $payload['name'] ?? null,
            'status' => $payload['status'] ?? null,
            'permissions' => isset($payload['permissions']) ? json_encode($payload['permissions']) : null,
        ], fn ($value) => $value !== null);

        if ($update) {
            ApiKey::updateById($id, $update);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function revokeApiKey(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $id = (int) $this->request->input('id', 0);
        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz anahtar'
            ], 422);
        }

        ApiKey::updateById($id, ['status' => 'revoked']);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function templates(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/templates/index', [
            'title' => 'Şablon Yönetimi',
            'clients' => Client::all()
        ]);
    }

    public function templatesData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(Template::all());
    }

    public function storeTemplate(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $clientId = isset($payload['client_id']) && $payload['client_id'] !== '' ? (int) $payload['client_id'] : null;
        $name = trim((string) ($payload['name'] ?? ''));
        $content = $payload['content'] ?? '';

        if ($name === '' || $content === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Ad ve içerik zorunludur'
            ], 422);
        }

        Template::createForClient($clientId, [
            'client_id' => $clientId,
            'name' => $name,
            'slug' => $payload['slug'] ?? uniqid('template-', false),
            'content' => $content,
            'status' => $payload['status'] ?? 'active'
        ]);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function updateTemplate(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $id = (int) ($payload['id'] ?? 0);
        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz şablon'
            ], 422);
        }

        $update = array_filter([
            'name' => $payload['name'] ?? null,
            'slug' => $payload['slug'] ?? null,
            'content' => $payload['content'] ?? null,
            'status' => $payload['status'] ?? null,
        ], fn ($value) => $value !== null);

        if ($update) {
            Template::updateById($id, $update);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function deleteTemplate(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $id = (int) $this->request->input('id', 0);
        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz şablon'
            ], 422);
        }

        Template::deleteById($id);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function notifications(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/notifications/index', [
            'title' => 'Bildirim Akışı'
        ]);
    }

    public function notificationsData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(Notification::recent(50));
    }

    public function subscriptions(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/subscriptions/index', [
            'title' => 'Abonelikler'
        ]);
    }

    public function subscriptionsData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(Subscription::all());
    }

    public function payments(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/payments/index', [
            'title' => 'Ödemeler'
        ]);
    }

    public function paymentsData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(Payment::all());
    }

    public function reports(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/reports/index', [
            'title' => 'Raporlama'
        ]);
    }

    public function reportDaily(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(ReportService::adminDailySummary());
    }

    public function reportPlatforms(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $sql = 'SELECT platform, COUNT(*) AS total FROM subscriptions WHERE platform IS NOT NULL GROUP BY platform ORDER BY total DESC';
        $stmt = Database::pdo()->query($sql);

        return $this->jsonResponse($stmt->fetchAll() ?: []);
    }

    public function settings(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/settings/index', [
            'title' => 'Sistem Ayarları',
            'mail' => Setting::getGroup('mail'),
            'notifications' => Setting::getGroup('notifications'),
            'iyzico' => Setting::getGroup('iyzico'),
        ]);
    }

    public function saveSettings(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();

        Setting::setGroup('mail', $payload['mail'] ?? []);
        Setting::setGroup('notifications', $payload['notifications'] ?? []);
        Setting::setGroup('iyzico', $payload['iyzico'] ?? []);

        return $this->jsonResponse(['status' => 'success']);
    }
}
