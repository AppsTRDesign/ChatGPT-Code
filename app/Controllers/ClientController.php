<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ApiKey;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Template;
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

        $stats = DashboardService::getClientStats((int) $client['id']);
        $recent = Notification::recent(10, (int) $client['id']);

        return $this->view('client/dashboard/index', [
            'title' => 'Müşteri Paneli',
            'stats' => $stats,
            'recent' => $recent
        ]);
    }

    public function notifications(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/notifications/index', [
            'title' => 'Bildirimler',
            'templates' => Template::allForClient((int) $this->currentClient()['id'])
        ]);
    }

    public function notificationsData(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->jsonResponse(Notification::allForClient((int) $this->currentClient()['id']));
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
            'tokens' => $payload['tokens'] ?? []
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

    public function billing(): string
    {
        if ($response = $this->requireClientAccess()) {
            return $response;
        }

        return $this->view('client/billing/index', [
            'title' => 'Faturalandırma',
            'payments' => Payment::allForClient((int) $this->currentClient()['id'])
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

        Payment::record((int) $client['id'], [
            'iyzico_payment_id' => $checkout['token'] ?? uniqid('iyzico_', false),
            'status' => $checkout['status'] ?? 'failure',
            'amount' => $payload['price'] ?? 0,
            'currency' => $payload['currency'] ?? 'TRY',
            'raw' => $checkout
        ]);

        $status = ($checkout['status'] ?? '') === 'success' ? 200 : 400;

        return $this->jsonResponse($checkout, $status);
    }

    public function reportsPage(): string
    {
        return $this->reports();
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
}
