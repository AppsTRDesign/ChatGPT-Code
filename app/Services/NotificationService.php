<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Subscription;

class NotificationService
{
    public static function dispatch(array $payload): array
    {
        $apiKeyValue = $payload['api_key'] ?? '';
        $apiKey = ApiKey::findActiveByKey($apiKeyValue);

        if (!$apiKey || !ApiKey::hasPermission($apiKey, 'notifications.dispatch')) {
            return [
                'status' => 'error',
                'message' => 'Geçersiz veya yetkisiz API anahtarı'
            ];
        }

        $clientId = (int) $apiKey['client_id'];
        $title = trim($payload['title'] ?? '');
        $message = trim($payload['message'] ?? '');

        if ($title === '' || $message === '') {
            ApiUsageLog::record($clientId, (int) $apiKey['id'], 'notifications.dispatch', 'error', ['reason' => 'missing_fields']);
            return [
                'status' => 'error',
                'message' => 'Başlık ve mesaj zorunludur'
            ];
        }

        $tokens = isset($payload['tokens']) && is_array($payload['tokens']) ? $payload['tokens'] : [];
        $subscriptions = Subscription::activeForClient($clientId, $tokens);

        if (!$subscriptions) {
            ApiUsageLog::record($clientId, (int) $apiKey['id'], 'notifications.dispatch', 'error', ['reason' => 'no_subscribers']);
            return [
                'status' => 'error',
                'message' => 'Aktif abone bulunamadı'
            ];
        }

        $notificationId = Notification::createForClient($clientId, [
            'template_id' => $payload['template_id'] ?? null,
            'title' => $title,
            'message' => $message,
            'target_url' => $payload['target_url'] ?? null,
            'status' => 'queued',
            'schedule_at' => $payload['schedule_at'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
        ]);

        $subscriptionIds = array_column($subscriptions, 'id');
        NotificationLog::createMany($notificationId, $subscriptionIds);
        Notification::updateStatus($notificationId, 'sending');

        static::notifyClientByEmail($clientId, $title, count($subscriptionIds));

        ApiUsageLog::record($clientId, (int) $apiKey['id'], 'notifications.dispatch', 'success', [
            'notification_id' => $notificationId,
            'recipients' => count($subscriptionIds)
        ]);

        return [
            'status' => 'queued',
            'notification_id' => $notificationId,
            'recipients' => count($subscriptionIds)
        ];
    }

    public static function registerToken(array $payload): array
    {
        $apiKeyValue = $payload['api_key'] ?? '';
        $apiKey = ApiKey::findActiveByKey($apiKeyValue);

        if (!$apiKey || !ApiKey::hasPermission($apiKey, 'tokens.register')) {
            return [
                'status' => 'error',
                'message' => 'Geçersiz API anahtarı'
            ];
        }

        $token = $payload['token'] ?? null;
        $endpoint = $payload['endpoint'] ?? null;

        if (!$token || !$endpoint) {
            ApiUsageLog::record((int) $apiKey['client_id'], (int) $apiKey['id'], 'tokens.register', 'error', ['reason' => 'missing_token']);
            return [
                'status' => 'error',
                'message' => 'Token ve endpoint zorunludur'
            ];
        }

        $geo = isset($payload['ip_address']) ? GeoLocationService::locate($payload['ip_address']) : [];
        $device = isset($payload['user_agent']) ? DeviceService::parse($payload['user_agent']) : [];

        $subscription = Subscription::upsert((int) $apiKey['client_id'], [
            'token' => $token,
            'endpoint' => $endpoint,
            'public_key' => $payload['public_key'] ?? null,
            'auth_token' => $payload['auth_token'] ?? null,
            'status' => 'active',
            'city' => $payload['city'] ?? ($geo['city'] ?? null),
            'country' => $payload['country'] ?? ($geo['country'] ?? null),
            'platform' => $payload['platform'] ?? ($device['os']['name'] ?? null),
            'browser' => $payload['browser'] ?? ($device['client']['name'] ?? null),
            'device_model' => $payload['device_model'] ?? ($device['model'] ?? null),
            'device_type' => $payload['device_type'] ?? ($device['device'] ?? null),
            'ip_address' => $payload['ip_address'] ?? null,
        ]);

        ApiKey::touchLastUsed((int) $apiKey['id']);

        ApiUsageLog::record((int) $apiKey['client_id'], (int) $apiKey['id'], 'tokens.register', 'success');

        return [
            'status' => 'success',
            'subscription' => $subscription
        ];
    }

    public static function pullInbox(array $payload): array
    {
        $apiKeyValue = $payload['api_key'] ?? '';
        $apiKey = ApiKey::findActiveByKey($apiKeyValue);

        if (!$apiKey || !ApiKey::hasPermission($apiKey, 'notifications.read')) {
            return [
                'status' => 'error',
                'message' => 'Geçersiz API anahtarı'
            ];
        }

        $token = $payload['token'] ?? '';
        if ($token === '') {
            ApiUsageLog::record((int) $apiKey['client_id'], (int) $apiKey['id'], 'notifications.inbox', 'error', ['reason' => 'missing_token']);
            return [
                'status' => 'error',
                'message' => 'Token zorunludur'
            ];
        }

        $clientId = (int) $apiKey['client_id'];
        $logs = NotificationLog::pendingForToken($clientId, $token);

        if (!$logs) {
            ApiUsageLog::record($clientId, (int) $apiKey['id'], 'notifications.inbox', 'success', ['count' => 0]);
            return [
                'status' => 'success',
                'notifications' => []
            ];
        }

        $logIds = array_column($logs, 'id');
        NotificationLog::markAsSent($logIds);

        $notifications = array_map(function (array $log) {
            return [
                'id' => (int) $log['notification_id'],
                'title' => $log['title'],
                'message' => $log['message'],
                'target_url' => $log['target_url'],
                'expires_at' => $log['expires_at']
            ];
        }, $logs);

        ApiUsageLog::record($clientId, (int) $apiKey['id'], 'notifications.inbox', 'success', ['count' => count($notifications)]);

        return [
            'status' => 'success',
            'notifications' => $notifications
        ];
    }

    public static function registerReceipt(array $payload): array
    {
        $apiKeyValue = $payload['api_key'] ?? '';
        $apiKey = ApiKey::findActiveByKey($apiKeyValue);

        if (!$apiKey || !ApiKey::hasPermission($apiKey, 'notifications.read')) {
            return [
                'status' => 'error',
                'message' => 'Geçersiz API anahtarı'
            ];
        }

        $notificationId = (int) ($payload['notification_id'] ?? 0);
        $token = $payload['token'] ?? '';
        $event = $payload['event'] ?? '';

        if (!$notificationId || $token === '' || $event === '') {
            ApiUsageLog::record((int) $apiKey['client_id'], (int) $apiKey['id'], 'notifications.receipt', 'error', ['reason' => 'missing_payload']);
            return [
                'status' => 'error',
                'message' => 'Eksik bildirim verisi'
            ];
        }

        $subscription = Subscription::findByToken($token);
        if (!$subscription) {
            ApiUsageLog::record((int) $apiKey['client_id'], (int) $apiKey['id'], 'notifications.receipt', 'error', ['reason' => 'subscription_not_found']);
            return [
                'status' => 'error',
                'message' => 'Abonelik bulunamadı'
            ];
        }

        NotificationLog::registerEvent($notificationId, (int) $subscription['id'], $event);

        ApiUsageLog::record((int) $apiKey['client_id'], (int) $apiKey['id'], 'notifications.receipt', 'success', [
            'event' => $event,
            'notification_id' => $notificationId
        ]);

        return [
            'status' => 'success'
        ];
    }

    protected static function notifyClientByEmail(int $clientId, string $title, int $recipientCount): void
    {
        $mailSettings = Setting::getGroup('mail');
        if (!$mailSettings) {
            return;
        }

        $recipients = Setting::get('notifications', 'default_report_email');
        if (!$recipients) {
            return;
        }

        MailerService::send([
            'host' => $mailSettings['host'] ?? 'smtp.example.com',
            'username' => $mailSettings['username'] ?? '',
            'password' => $mailSettings['password'] ?? '',
            'port' => $mailSettings['port'] ?? 587,
            'encryption' => $mailSettings['encryption'] ?? 'tls',
            'from_email' => $mailSettings['from_email'] ?? 'noreply@example.com',
            'from_name' => $mailSettings['from_name'] ?? 'NoaSoft Web Push',
            'to_email' => $recipients,
            'subject' => sprintf('Yeni bildirim kuyruğa alındı (#%d)', $clientId),
            'body' => sprintf('<p><strong>%s</strong> başlıklı bildirim %d aboneye gönderilmek üzere kuyruğa alındı.</p>', $title, $recipientCount)
        ]);
    }
}
