<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ApiKey;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\ClientSite;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SupportRequest;
use App\Models\Template;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\ReportService;

class ClientController extends Controller
{
    public function home(): string
    {
        return $this->view('client/dashboard/overview', [
            'title' => 'Web Push Platformu'
        ]);
    }

    public function dashboard(): string
    {
        if ($response = $this->requireAuth(['client', 'admin'])) {
            return $response;
        }

        $client = $this->currentClient();
        if (!$client) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Müşteri kaydı bulunamadı'
            ], 404);
        }

        $clientId = (int) $client['id'];
        $stats = DashboardService::getClientStats($clientId);
        $recent = Notification::recent(10, $clientId);
        $history = ReportService::notificationHistory($clientId, 15);
        $apiSummary = ReportService::apiUsageSummaryForClient($clientId);

        return $this->view('client/dashboard/index', [
            'title' => 'Müşteri Paneli',
            'stats' => $stats,
            'recent' => $recent,
            'history' => $history,
            'apiSummary' => $apiSummary
        ]);
    }

    public function notifications(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $client = $this->currentClient();
        $clientId = (int) $client['id'];

        return $this->view('client/notifications/index', [
            'title' => 'Bildirimler',
            'templates' => Template::allForClient($clientId),
            'sites' => ClientSite::activeForClient($clientId),
            'defaultLanguage' => $client['default_language'] ?? 'tr',
            'languages' => $this->languageOptions()
        ]);
    }

    public function notificationsData(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(Notification::allForClient((int) $this->currentClient()['id']));
    }

    public function notificationMetrics(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $client = $this->currentClient();
        $notificationId = (int) $this->request->input('notification_id', 0);
        $range = $this->request->input('range', 'daily');

        if ($notificationId) {
            $notification = Notification::find($notificationId);
            if (!$notification || (int) $notification['client_id'] !== (int) $client['id']) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bildirim bulunamadı'
                ], 404);
            }
        }

        $series = ReportService::clientNotificationSeries((int) $client['id'], $range, $notificationId ?: null);
        $breakdown = ReportService::notificationBreakdown((int) $client['id'], $notificationId ?: null);

        return $this->jsonResponse([
            'status' => 'success',
            'series' => $series,
            'breakdown' => $breakdown
        ]);
    }

    public function storeNotification(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();

        $dispatchPayload = [
            'api_key' => $payload['api_key'] ?? $this->defaultApiKey(),
            'title' => $payload['title'] ?? '',
            'message' => $payload['message'] ?? '',
            'target_url' => $payload['target_url'] ?? null,
            'template_id' => $payload['template_id'] ?? null,
            'tokens' => $payload['tokens'] ?? [],
            'language' => $payload['language'] ?? null,
            'site_id' => $payload['site_id'] ?? null,
            'site_identifier' => $payload['site_identifier'] ?? null,
            'country' => $payload['country'] ?? null,
            'city' => $payload['city'] ?? null,
            'platform' => $payload['platform'] ?? null,
            'browser' => $payload['browser'] ?? null,
            'device_type' => $payload['device_type'] ?? null,
            'device_model' => $payload['device_model'] ?? null,
            'button_text' => $payload['button_text'] ?? null,
            'button_url' => $payload['button_url'] ?? null,
            'duration_type' => $payload['duration_type'] ?? 'permanent',
            'duration_value' => $payload['duration_value'] ?? null,
            'duration_unit' => $payload['duration_unit'] ?? null,
            'image_path' => $payload['image_path'] ?? null,
            'icon_path' => $payload['icon_path'] ?? null,
            'schedule_at' => $payload['schedule_at'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
        ];

        $response = NotificationService::dispatch($dispatchPayload);
        $status = $response['status'] === 'queued' ? 200 : 400;

        return $this->jsonResponse($response, $status);
    }

    public function updateNotification(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $id = (int) ($payload['id'] ?? 0);
        if (!$id) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz bildirim'
            ], 422);
        }

        $client = $this->currentClient();
        $notification = Notification::find($id);
        if (!$notification || (int) $notification['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Yetkisiz işlem'
            ], 403);
        }

        $update = array_filter([
            'title' => $payload['title'] ?? null,
            'message' => $payload['message'] ?? null,
            'target_url' => $payload['target_url'] ?? null,
            'status' => $payload['status'] ?? null,
        ], fn ($value) => $value !== null);

        if ($update) {
            Notification::updateById($id, $update);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function deleteNotification(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $id = (int) $this->request->input('id', 0);
        $client = $this->currentClient();
        $notification = Notification::find($id);
        if (!$notification || (int) $notification['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Yetkisiz işlem'
            ], 403);
        }

        Notification::deleteById($id);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function notificationHistory(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $client = $this->currentClient();
        $limit = (int) ($this->request->input('limit', 50));

        return $this->jsonResponse([
            'status' => 'success',
            'items' => ReportService::notificationHistory((int) $client['id'], max(10, min($limit, 200)))
        ]);
    }

    public function uploadNotificationAsset(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Dosya yüklenemedi'
            ], 422);
        }

        $file = $_FILES['file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!in_array($extension, $allowed, true)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Desteklenmeyen dosya türü'
            ], 422);
        }

        $uploadDir = __DIR__ . '/../../public/uploads/notifications';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Yükleme dizini oluşturulamadı'
            ], 500);
        }

        $filename = uniqid('notification_', true) . '.' . $extension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Dosya taşınamadı'
            ], 500);
        }

        return $this->jsonResponse([
            'status' => 'success',
            'path' => '/uploads/notifications/' . $filename
        ]);
    }

    public function templates(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/templates/index', [
            'title' => 'Şablonlar'
        ]);
    }

    public function templatesData(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(Template::allForClient((int) $this->currentClient()['id']));
    }

    public function storeTemplate(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();

        $name = trim((string) ($payload['name'] ?? ''));
        $content = $payload['content'] ?? '';

        if ($name === '' || $content === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Ad ve içerik zorunludur'
            ], 422);
        }

        Template::createForClient((int) $client['id'], [
            'name' => $name,
            'slug' => $payload['slug'] ?? uniqid('template-', false),
            'content' => $content,
            'status' => $payload['status'] ?? 'active'
        ]);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function updateTemplate(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $id = (int) ($payload['id'] ?? 0);

        $template = Template::find($id);
        if (!$template || ($template['client_id'] !== null && (int) $template['client_id'] !== (int) $client['id'])) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Şablon bulunamadı'
            ], 404);
        }

        $update = array_filter([
            'name' => $payload['name'] ?? null,
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
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $id = (int) $this->request->input('id', 0);
        $client = $this->currentClient();
        $template = Template::find($id);
        if (!$template || (int) $template['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Şablon silinemedi'
            ], 403);
        }

        Template::deleteById($id);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function apiKeys(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/api/index', [
            'title' => 'API Yönetimi'
        ]);
    }

    public function apiKeysData(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(ApiKey::allForClient((int) $this->currentClient()['id']));
    }

    public function storeApiKey(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $name = trim((string) ($payload['name'] ?? ''));

        if ($name === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Ad zorunludur'
            ], 422);
        }

        $apiKeyId = ApiKey::createForClient((int) $client['id'], [
            'name' => $name,
            'permissions' => $payload['permissions'] ?? ['notifications.dispatch', 'tokens.register', 'notifications.read']
        ]);

        return $this->jsonResponse([
            'status' => 'success',
            'api_key' => ApiKey::find($apiKeyId)
        ]);
    }

    public function updateApiKey(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $id = (int) ($payload['id'] ?? 0);
        $apiKey = ApiKey::find($id);

        if (!$apiKey || (int) $apiKey['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Anahtar bulunamadı'
            ], 404);
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

    public function sites(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/sites/index', [
            'title' => 'Siteler'
        ]);
    }

    public function sitesData(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(ClientSite::forClient((int) $this->currentClient()['id']));
    }

    public function storeSite(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $name = trim((string) ($payload['name'] ?? ''));
        $domain = trim((string) ($payload['domain'] ?? ''));

        if ($name === '' || $domain === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Ad ve alan adı zorunludur'
            ], 422);
        }

        $identifier = $payload['api_identifier'] ?? uniqid('site_', false);
        ClientSite::create([
            'client_id' => (int) $client['id'],
            'name' => $name,
            'domain' => $domain,
            'api_identifier' => $identifier,
            'status' => $payload['status'] ?? 'active',
            'description' => $payload['description'] ?? null
        ]);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function updateSite(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $id = (int) ($payload['id'] ?? 0);

        $site = ClientSite::find($id);
        if (!$site || (int) $site['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Site bulunamadı'
            ], 404);
        }

        $update = array_filter([
            'name' => $payload['name'] ?? null,
            'domain' => $payload['domain'] ?? null,
            'status' => $payload['status'] ?? null,
            'description' => $payload['description'] ?? null
        ], fn ($value) => $value !== null);

        if ($update) {
            ClientSite::updateById($id, $update);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function deleteSite(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $id = (int) $this->request->input('id', 0);
        $client = $this->currentClient();
        $site = ClientSite::find($id);
        if (!$site || (int) $site['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Site silinemedi'
            ], 403);
        }

        ClientSite::deleteById($id);

        return $this->jsonResponse(['status' => 'success']);
    }

    public function subscriptions(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/subscriptions/index', [
            'title' => 'Abonelikler'
        ]);
    }

    public function subscriptionsData(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(Subscription::allForClient((int) $this->currentClient()['id']));
    }

    public function updateSubscriptionStatus(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $client = $this->currentClient();
        $id = (int) ($this->request->input('id', 0));
        $status = $this->request->input('status', 'revoked');

        $subscription = Subscription::find($id);
        if (!$subscription || (int) $subscription['client_id'] !== (int) $client['id']) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Abonelik bulunamadı'
            ], 404);
        }

        Subscription::updateStatus($id, in_array($status, ['active', 'revoked', 'unsubscribed'], true) ? $status : 'revoked');

        return $this->jsonResponse(['status' => 'success']);
    }

    public function reports(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/reports/index', [
            'title' => 'Raporlar'
        ]);
    }

    public function reportEngagement(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(ReportService::clientEngagement((int) $this->currentClient()['id']));
    }

    public function reportTemplates(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(ReportService::topTemplates((int) $this->currentClient()['id']));
    }

    public function apiUsageSeries(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $range = $this->request->input('range', 'daily');

        return $this->jsonResponse([
            'status' => 'success',
            'series' => ReportService::apiUsageSeriesForClient((int) $this->currentClient()['id'], $range)
        ]);
    }

    public function apiUsageSummary(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse([
            'status' => 'success',
            'items' => ReportService::apiUsageSummaryForClient((int) $this->currentClient()['id'])
        ]);
    }

    public function billing(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $clientId = (int) $this->currentClient()['id'];

        return $this->view('client/billing/index', [
            'title' => 'Faturalandırma',
            'payments' => Payment::allForClient($clientId),
            'packages' => Package::allActive(),
            'clientPackages' => ClientPackage::forClient($clientId)
        ]);
    }

    public function createCheckout(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $iyzico = Setting::getGroup('iyzico');

        $checkout = PaymentService::createCheckout([
            'api_key' => $iyzico['api_key'] ?? '',
            'secret_key' => $iyzico['secret_key'] ?? '',
            'base_url' => $iyzico['base_url'] ?? 'https://sandbox-api.iyzipay.com',
            'price' => $payload['price'] ?? '0.0',
            'paid_price' => $payload['paid_price'] ?? $payload['price'] ?? '0.0',
            'currency' => $payload['currency'] ?? 'TRY',
            'callback_url' => $payload['callback_url'] ?? '',
            'basket_id' => $payload['basket_id'] ?? 'subscription-' . $client['id']
        ]);

        $paymentId = Payment::record((int) $client['id'], [
            'iyzico_payment_id' => $checkout['token'] ?? uniqid('iyzico_', false),
            'status' => $checkout['status'] ?? 'failure',
            'amount' => $payload['price'] ?? 0,
            'currency' => $payload['currency'] ?? 'TRY',
            'method' => 'iyzico',
            'raw' => $checkout
        ]);

        if (!empty($payload['package_id'])) {
            $packageId = (int) $payload['package_id'];
            $package = Package::find($packageId);
            if ($package) {
                $clientPackageId = ClientPackage::create([
                    'client_id' => (int) $client['id'],
                    'package_id' => $packageId,
                    'status' => ($checkout['status'] ?? '') === 'success' ? 'active' : 'pending',
                    'payment_id' => $paymentId,
                    'payment_method' => 'iyzico',
                    'amount' => $payload['price'] ?? ($package['price'] ?? 0),
                    'currency' => $payload['currency'] ?? ($package['currency'] ?? 'TRY'),
                    'auto_approved' => ($checkout['status'] ?? '') === 'success' ? 1 : 0,
                    'requested_at' => date('Y-m-d H:i:s')
                ]);

                if (($checkout['status'] ?? '') === 'success') {
                    $expiresAt = null;
                    if (!empty($package['duration_days'])) {
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) $package['duration_days'] . ' days'));
                    }
                    ClientPackage::activate($clientPackageId, $expiresAt);
                }
            }
        }

        $status = ($checkout['status'] ?? '') === 'success' ? 200 : 400;

        return $this->jsonResponse($checkout, $status);
    }

    public function profile(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/profile/index', [
            'title' => 'Profilim',
            'client' => $this->currentClient(),
            'user' => $this->currentUser(),
            'languages' => $this->languageOptions()
        ]);
    }

    public function updateProfile(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $client = $this->currentClient();
        $user = $this->currentUser();
        $payload = $this->payload();

        $emailInput = array_key_exists('email', $payload) ? trim((string) $payload['email']) : null;
        $usernameInput = array_key_exists('username', $payload) ? trim((string) $payload['username']) : null;
        $passwordInput = array_key_exists('password', $payload) ? (string) $payload['password'] : null;

        if ($emailInput !== null && $emailInput !== '' && !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lütfen geçerli bir e-posta adresi girin.'
            ], 422);
        }

        if ($usernameInput !== null && $usernameInput !== '' && mb_strlen($usernameInput) < 3) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Kullanıcı adı en az 3 karakter olmalıdır.'
            ], 422);
        }

        if ($passwordInput !== null && $passwordInput !== '' && mb_strlen($passwordInput) < 8) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Şifre en az 8 karakter olmalıdır.'
            ], 422);
        }

        $clientUpdate = [];

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            $clientUpdate['name'] = $name !== '' ? $name : null;
        }

        if ($emailInput !== null) {
            $clientUpdate['email'] = $emailInput !== '' ? $emailInput : null;
        }

        if (array_key_exists('phone', $payload)) {
            $phone = trim((string) $payload['phone']);
            $clientUpdate['phone'] = $phone !== '' ? $phone : null;
        }

        if (array_key_exists('domain', $payload)) {
            $domain = trim((string) $payload['domain']);
            $clientUpdate['domain'] = $domain !== '' ? $domain : null;
        }

        if (array_key_exists('default_language', $payload)) {
            $language = trim((string) $payload['default_language']);
            $clientUpdate['default_language'] = $language !== '' ? $language : null;
        }

        if (array_key_exists('notes', $payload)) {
            $clientUpdate['notes'] = trim((string) $payload['notes']);
        }

        if ($clientUpdate !== []) {
            Client::updateById((int) $client['id'], $clientUpdate);
        }

        $userUpdate = [];

        if ($emailInput !== null) {
            $userUpdate['email'] = $emailInput !== '' ? $emailInput : null;
        }

        if ($usernameInput !== null) {
            $userUpdate['username'] = $usernameInput !== '' ? $usernameInput : null;
        }

        if ($passwordInput !== null && $passwordInput !== '') {
            $userUpdate['password'] = password_hash($passwordInput, PASSWORD_BCRYPT);
        }

        if ($userUpdate !== []) {
            User::updateById((int) $user['id'], $userUpdate);
        }

        return $this->jsonResponse(['status' => 'success']);
    }

    public function notifyBankTransfer(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        $payload = $this->payload();
        $client = $this->currentClient();
        $packageId = (int) ($payload['package_id'] ?? 0);
        $package = $packageId ? Package::find($packageId) : null;

        if (!$package) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçersiz paket seçimi'
            ], 422);
        }

        $amountInput = $payload['amount'] ?? null;
        if ($amountInput === null || $amountInput === '') {
            $amount = isset($package['price']) ? (float) $package['price'] : 0;
        } else {
            if (is_string($amountInput)) {
                $normalized = preg_replace('/[^0-9,\.]/', '', $amountInput);
                $normalized = str_replace(',', '.', (string) $normalized);
                $amount = (float) $normalized;
            } else {
                $amount = (float) $amountInput;
            }
        }

        if ($amount <= 0) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçerli bir tutar belirtin.'
            ], 422);
        }

        $currencyInput = $payload['currency'] ?? ($package['currency'] ?? 'TRY');
        $currency = strtoupper(trim((string) $currencyInput));

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Geçerli bir para birimi seçin.'
            ], 422);
        }

        $note = array_key_exists('note', $payload) ? trim((string) $payload['note']) : null;
        $reference = array_key_exists('reference', $payload) ? trim((string) $payload['reference']) : null;

        $paymentId = Payment::record((int) $client['id'], [
            'method' => 'bank_transfer',
            'status' => 'initiated',
            'amount' => $amount,
            'currency' => $currency,
            'note' => $note !== '' ? $note : null,
            'raw' => [
                'type' => 'bank_transfer',
                'reference' => $reference !== '' ? $reference : null
            ]
        ]);

        ClientPackage::create([
            'client_id' => (int) $client['id'],
            'package_id' => $packageId,
            'status' => 'pending',
            'payment_id' => $paymentId,
            'payment_method' => 'bank_transfer',
            'amount' => $amount,
            'currency' => $currency,
            'note' => $note !== '' ? $note : null,
            'requested_at' => date('Y-m-d H:i:s')
        ]);

        return $this->jsonResponse([
            'status' => 'success',
            'message' => 'Havale bildiriminiz alınmıştır'
        ]);
    }

    public function reportsPage(): string
    {
        return $this->reports();
    }

    public function submitSupport(): string
    {
        $payload = $this->payload();
        $client = $this->currentClient();
        $email = trim((string) ($payload['email'] ?? ($client['email'] ?? '')));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));

        if ($email === '' || $subject === '' || $message === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lütfen gerekli alanları doldurun'
            ], 422);
        }

        SupportRequest::create([
            'client_id' => $client['id'] ?? null,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ]);

        return $this->jsonResponse(['status' => 'success']);
    }

    protected function requireClientAccess(): ?string
    {
        if ($response = $this->requireAuth(['client', 'admin'])) {
            return $response;
        }

        if (!$this->currentClient()) {
            if ($this->wantsJson()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Müşteri bilgisi bulunamadı'
                ], 404);
            }

            http_response_code(404);
            return 'Müşteri bilgisi bulunamadı';
        }

        return null;
    }

    protected function defaultApiKey(): ?string
    {
        $client = $this->currentClient();
        if (!$client) {
            return null;
        }

        $keys = ApiKey::allForClient((int) $client['id']);
        return $keys[0]['api_key'] ?? null;
    }

    protected function languageOptions(): array
    {
        return [
            'tr' => 'Türkçe',
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español',
            'ar' => 'العربية'
        ];
    }
}
