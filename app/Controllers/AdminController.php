<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ApiKey;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use App\Models\Package;
use App\Models\ApiUsageLog;
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
            'title' => 'Müşteri Yönetimi',
            'packages' => Package::allActive()
        ]);
    }

    public function clientsData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $clients = array_map(function (array $client) {
            $client['mail_verified'] = !empty($client['mail_verified_at']);
            $client['email_verified_at'] = $client['email_verified_at'] ?? null;
            $client['is_blocked'] = (bool) ($client['is_blocked'] ?? false);
            $client['login_block'] = $client['is_blocked'];
            $client['login_banned_until'] = $client['login_banned_until'] ?? null;
            $client['active_package_count'] = (int) ($client['active_package_count'] ?? 0);

            return $client;
        }, Client::allWithStats());

        return $this->jsonResponse($clients);
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
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $notes = trim((string) ($payload['notes'] ?? ''));
        $mailVerified = $this->boolValue($payload['mail_verified'] ?? false);
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
                'email' => $email !== '' ? $email : null,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => 'client',
                'email_verified_at' => $mailVerified ? date('Y-m-d H:i:s') : null
            ]);

            $clientId = Client::create([
                'user_id' => $userId,
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'domain' => $domain,
                'phone' => $phone !== '' ? $phone : null,
                'status' => $status,
                'mail_verified_at' => $mailVerified ? date('Y-m-d H:i:s') : null,
                'notes' => $notes !== '' ? $notes : null
            ]);

            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($packageId) {
                $this->assignPackageToClient($clientId, $packageId, [
                    'payment_method' => $payload['payment_method'] ?? 'manual',
                    'note' => $payload['package_note'] ?? null,
                    'status' => $payload['package_status'] ?? 'active'
                ]);
            }
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
            'email' => $payload['email'] ?? null,
            'domain' => $payload['domain'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'status' => $payload['status'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if (isset($payload['mail_verified'])) {
            $updateClient['mail_verified_at'] = $this->boolValue($payload['mail_verified'])
                ? date('Y-m-d H:i:s')
                : null;
        }

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
        if (array_key_exists('email', $payload)) {
            $updateUser['email'] = $payload['email'] !== '' ? $payload['email'] : null;
        }

        if (isset($payload['mail_verified'])) {
            $updateUser['email_verified_at'] = $this->boolValue($payload['mail_verified'])
                ? date('Y-m-d H:i:s')
                : null;
        }

        if (isset($payload['login_block'])) {
            $updateUser['is_blocked'] = $this->boolValue($payload['login_block']) ? 1 : 0;
        }

        if (array_key_exists('login_banned_until', $payload)) {
            $banUntil = trim((string) $payload['login_banned_until']);
            if ($banUntil !== '') {
                $timestamp = strtotime($banUntil);
                $updateUser['login_banned_until'] = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
            } else {
                $updateUser['login_banned_until'] = null;
            }
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

    public function purchases(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/purchases/index', [
            'title' => 'Satın Alımlar',
            'clients' => Client::all(),
            'packages' => Package::all()
        ]);
    }

    public function purchasesData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $clientId = (int) $this->request->input('client_id', 0);
        if ($clientId) {
            return $this->jsonResponse(ClientPackage::forClient($clientId));
        }

        return $this->jsonResponse(ClientPackage::allWithRelations());
    }

    public function storeClientPackage(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $clientId = (int) ($payload['client_id'] ?? 0);
        $packageId = (int) ($payload['package_id'] ?? 0);
        $status = $payload['status'] ?? 'pending';
        $paymentMethod = $payload['payment_method'] ?? 'manual';

        if (!$clientId || !$packageId) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Müşteri ve paket seçilmelidir'
            ], 422);
        }

        if (!in_array($status, ['pending', 'active', 'cancelled', 'rejected', 'partial'], true)) {
            $status = 'pending';
        }

        $this->assignPackageToClient($clientId, $packageId, [
            'status' => $status,
            'payment_method' => $paymentMethod,
            'note' => $payload['note'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null
        ]);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function updateClientPackageStatus(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $packageId = (int) ($payload['id'] ?? 0);
        $action = $payload['action'] ?? '';

        if (!$packageId || !in_array($action, ['approve', 'reject', 'cancel', 'partial'], true)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz işlem'
            ], 422);
        }

        $record = ClientPackage::find($packageId);
        if (!$record) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Satın alım bulunamadı'
            ], 404);
        }

        $note = $payload['note'] ?? null;

        if ($action === 'approve') {
            $package = Package::find((int) $record['package_id']);
            $expiresAt = null;
            if ($package && !empty($package['duration_days'])) {
                $expiresAt = $this->calculatePackageExpiry((int) $package['duration_days']);
            }

            ClientPackage::activate($packageId, $expiresAt);

            if (!empty($record['payment_id'])) {
                Payment::updateStatus((int) $record['payment_id'], 'paid');
            }
        } elseif ($action === 'reject') {
            ClientPackage::reject($packageId, $note);
            if (!empty($record['payment_id'])) {
                Payment::updateStatus((int) $record['payment_id'], 'failed', $note);
            }
        } elseif ($action === 'cancel') {
            ClientPackage::cancel($packageId, $note);
            if (!empty($record['payment_id'])) {
                Payment::updateStatus((int) $record['payment_id'], 'refunded', $note);
            }
        } elseif ($action === 'partial') {
            ClientPackage::markPartial($packageId, $note);
            if (!empty($record['payment_id'])) {
                Payment::updateStatus((int) $record['payment_id'], 'partial', $note);
            }
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function packages(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/packages/index', [
            'title' => 'Paket Yönetimi'
        ]);
    }

    public function packagesData(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(Package::all());
    }

    public function storePackage(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $name = trim((string) ($payload['name'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));

        if ($name === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Paket adı zorunludur'
            ], 422);
        }

        if ($slug === '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        }

        $packageData = [
            'name' => $name,
            'slug' => $slug,
            'monthly_limit' => $payload['monthly_limit'] ?? null,
            'duration_days' => $payload['duration_days'] ?? null,
            'site_limit' => $payload['site_limit'] ?? null,
            'price' => $payload['price'] ?? 0,
            'currency' => $payload['currency'] ?? 'TRY',
            'description' => $payload['description'] ?? null,
            'features' => $payload['features'] ?? [],
            'allowed_features' => $payload['allowed_features'] ?? [],
            'status' => $payload['status'] ?? 'active'
        ];

        $packageData = Package::normalisePayload($packageData);
        Package::create($packageData);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function updatePackage(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();
        $id = (int) ($payload['id'] ?? 0);

        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz paket'
            ], 422);
        }

        $update = array_filter([
            'name' => $payload['name'] ?? null,
            'slug' => $payload['slug'] ?? null,
            'monthly_limit' => $payload['monthly_limit'] ?? null,
            'duration_days' => $payload['duration_days'] ?? null,
            'site_limit' => $payload['site_limit'] ?? null,
            'price' => $payload['price'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'description' => $payload['description'] ?? null,
            'status' => $payload['status'] ?? null,
        ], fn ($value) => $value !== null);

        if (isset($payload['features'])) {
            $update['features'] = $payload['features'];
        }

        if (isset($payload['allowed_features'])) {
            $update['allowed_features'] = $payload['allowed_features'];
        }

        if (!$update) {
            return $this->jsonResponse(['status' => 'success']);
        }

        $update = Package::normalisePayload($update);
        Package::updateById($id, $update);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function deletePackage(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $id = (int) $this->request->input('id', 0);
        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz paket'
            ], 422);
        }

        Package::deleteById($id);

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

    public function reportApiUsage(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(ApiUsageLog::dailyUsage());
    }

    public function reportApiSummary(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->jsonResponse(ApiUsageLog::summary());
    }

    public function reportRevenue(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $range = $this->normalizedRange($this->request->input('range', 'monthly'));

        return $this->jsonResponse(Payment::revenueSeries($range));
    }

    public function reportTraffic(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $range = $this->normalizedRange($this->request->input('range', 'daily'));

        return $this->jsonResponse(ReportService::trafficSeries($range));
    }

    public function reportMembership(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $range = $this->normalizedRange($this->request->input('range', 'daily'));

        return $this->jsonResponse(ReportService::membershipSeries($range));
    }

    public function exportTrafficReport(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $range = $this->normalizedRange($this->request->input('range', 'daily'));
        $format = $this->normalizedFormat($this->request->input('format', 'excel'));
        $data = ReportService::trafficSeries($range);

        return $this->exportSeries(
            $data,
            [
                'bucket' => 'Dönem',
                'total' => 'Toplam',
                'sent' => 'Başarılı',
                'failed' => 'Hatalı'
            ],
            sprintf('Bildirim Trafiği (%s)', ucfirst($range)),
            sprintf('trafik-%s', $range),
            $format
        );
    }

    public function exportMembershipReport(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $range = $this->normalizedRange($this->request->input('range', 'daily'));
        $format = $this->normalizedFormat($this->request->input('format', 'excel'));
        $data = ReportService::membershipSeries($range);

        return $this->exportSeries(
            $data,
            [
                'bucket' => 'Dönem',
                'new_members' => 'Yeni Üye',
                'activated_packages' => 'Aktif Paket'
            ],
            sprintf('Üyelik Analizi (%s)', ucfirst($range)),
            sprintf('uyelik-%s', $range),
            $format
        );
    }

    public function exportRevenueReport(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $range = $this->normalizedRange($this->request->input('range', 'monthly'));
        $format = $this->normalizedFormat($this->request->input('format', 'excel'));
        $data = Payment::revenueSeries($range);

        return $this->exportSeries(
            $data,
            [
                'bucket' => 'Dönem',
                'total' => 'Gelir (₺)'
            ],
            sprintf('Gelir Analizi (%s)', ucfirst($range)),
            sprintf('gelir-%s', $range),
            $format
        );
    }

    public function settings(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        return $this->view('admin/settings/index', [
            'title' => 'Sistem Ayarları',
            'general' => Setting::getGroup('general'),
            'branding' => Setting::getGroup('branding'),
            'payments' => Setting::getGroup('payments'),
            'mail' => Setting::getGroup('mail'),
            'notifications' => Setting::getGroup('notifications'),
            'iyzico' => Setting::getGroup('iyzico'),
            'firebase' => Setting::getGroup('firebase'),
            'analytics' => Setting::getGroup('analytics'),
        ]);
    }

    public function saveSettings(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        $payload = $this->payload();

        $payments = $payload['payments'] ?? [];
        $payments['iyzico_enabled'] = $this->boolValue($payments['iyzico_enabled'] ?? 0) ? '1' : '0';
        $payments['bank_enabled'] = $this->boolValue($payments['bank_enabled'] ?? 0) ? '1' : '0';
        $payments['invoice_required'] = $this->boolValue($payments['invoice_required'] ?? 0) ? '1' : '0';

        $mail = $payload['mail'] ?? [];
        $mail['enabled'] = $this->boolValue($mail['enabled'] ?? 0) ? '1' : '0';

        $notifications = $payload['notifications'] ?? [];
        $notifications['geo_enabled'] = $this->boolValue($notifications['geo_enabled'] ?? 0) ? '1' : '0';

        $firebase = $payload['firebase'] ?? [];
        $firebase['enabled'] = $this->boolValue($firebase['enabled'] ?? 0) ? '1' : '0';

        $analytics = $payload['analytics'] ?? [];
        $analytics['enabled'] = $this->boolValue($analytics['enabled'] ?? 0) ? '1' : '0';

        Setting::setGroup('general', $payload['general'] ?? []);
        Setting::setGroup('branding', $payload['branding'] ?? []);
        Setting::setGroup('payments', $payments);
        Setting::setGroup('mail', $mail);
        Setting::setGroup('notifications', $notifications);
        Setting::setGroup('iyzico', $payload['iyzico'] ?? []);
        Setting::setGroup('firebase', $firebase);
        Setting::setGroup('analytics', $analytics);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function uploadBrandAsset(): string
    {
        if ($response = $this->requireAuth(['admin'])) {
            return $response;
        }

        if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name'])) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Dosya bulunamadı'
            ], 422);
        }

        $type = $this->request->input('type', 'logo');
        $allowedTypes = ['logo', 'favicon'];
        if (!in_array($type, $allowedTypes, true)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz dosya türü'
            ], 422);
        }

        $file = $_FILES['file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg', 'ico', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Desteklenmeyen dosya formatı'
            ], 422);
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/branding';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Yükleme dizini oluşturulamadı'
            ], 500);
        }

        $filename = sprintf('%s-%s.%s', $type, uniqid('', true), $extension);
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Dosya yüklenemedi'
            ], 500);
        }

        $relativePath = '/uploads/branding/' . $filename;
        Setting::set('branding', $type, $relativePath);

        return $this->jsonResponse([
            'status' => 'success',
            'path' => $relativePath
        ]);
    }

    protected function boolValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower((string) $value);

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    protected function assignPackageToClient(int $clientId, int $packageId, array $options = []): void
    {
        $package = Package::find($packageId);
        if (!$package) {
            return;
        }

        $status = $options['status'] ?? 'pending';
        $expiresAt = null;

        if (!empty($package['duration_days'])) {
            $expiresAt = $this->calculatePackageExpiry((int) $package['duration_days']);
        }

        $payload = [
            'client_id' => $clientId,
            'package_id' => $packageId,
            'status' => $status,
            'payment_method' => $options['payment_method'] ?? 'manual',
            'note' => $options['note'] ?? null,
            'amount' => isset($options['amount']) ? (float) $options['amount'] : ($package['price'] ?? 0),
            'currency' => $options['currency'] ?? ($package['currency'] ?? 'TRY'),
            'auto_approved' => $status === 'active' ? 1 : 0,
            'expires_at' => $status === 'active' ? $expiresAt : null,
        ];

        $packageRecordId = ClientPackage::create($payload);

        if ($status === 'active') {
            ClientPackage::activate($packageRecordId, $expiresAt);
        }
    }

    protected function calculatePackageExpiry(int $durationDays): string
    {
        $start = new \DateTimeImmutable();
        return $start->modify('+' . $durationDays . ' days')->format('Y-m-d H:i:s');
    }

    protected function normalizedRange(string $range): string
    {
        $range = strtolower($range);
        $allowed = ['daily', 'weekly', 'monthly', 'yearly'];

        return in_array($range, $allowed, true) ? $range : 'daily';
    }

    protected function normalizedFormat(string $format): string
    {
        $format = strtolower($format);
        return in_array($format, ['excel', 'pdf'], true) ? $format : 'excel';
    }

    protected function exportSeries(array $records, array $headerMap, string $title, string $filename, string $format): string
    {
        $rows = [];
        $keys = array_keys($headerMap);
        foreach ($records as $record) {
            $row = [];
            foreach ($keys as $key) {
                $value = $record[$key] ?? '';
                if (is_numeric($value)) {
                    $value = str_contains((string) $value, '.')
                        ? number_format((float) $value, 2, ',', '.')
                        : (string) (int) $value;
                }
                $row[] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $rows[] = $row;
        }

        if ($format === 'pdf') {
            $pdf = $this->buildPdf($title, array_values($headerMap), $rows);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');

            return $pdf;
        }

        $csv = $this->buildCsv(array_values($headerMap), $rows);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        return $csv;
    }

    protected function buildCsv(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($stream, $row, ';');
        }
        rewind($stream);
        return stream_get_contents($stream) ?: '';
    }

    protected function buildPdf(string $title, array $headers, array $rows): string
    {
        $lines = [$title, str_repeat('-', 72), implode(' | ', $headers)];
        foreach ($rows as $row) {
            $lines[] = implode(' | ', $row);
        }

        $content = "BT /F1 12 Tf 50 780 Td 16 TL\n";
        $first = true;
        foreach ($lines as $line) {
            $safe = $this->pdfEscape($line);
            if ($first) {
                $content .= sprintf('(%s) Tj\n', $safe);
                $first = false;
            } else {
                $content .= sprintf('T* (%s) Tj\n', $safe);
            }
        }
        $content .= 'ET';

        $length = strlen($content);
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $append = function (string $chunk) use (&$pdf, &$offsets): void {
            $offsets[] = strlen($pdf);
            $pdf .= $chunk;
        };

        $append("1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n");
        $append("2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n");
        $append("3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n");
        $append("4 0 obj << /Length {$length} >> stream\n{$content}\nendstream endobj\n");
        $append("5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n");

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref\n0 ' . (count($offsets) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer << /Size " . (count($offsets) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
    }
}
