<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Subscription;
use App\Models\Template;

class DashboardService
{
    public static function getAdminStats(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'active_subscriptions' => Subscription::countActive(),
            'template_count' => Template::count(),
            'total_clients' => Client::count()
        ];
    }

    public static function getClientStats(int $clientId): array
    {
        return [
            'sent_notifications' => Notification::countForClient($clientId),
            'active_tokens' => Subscription::countActiveByClient($clientId),
            'available_templates' => Template::countAvailableForClient($clientId),
            'click_rate' => NotificationLog::averageClickRateForClient($clientId)
        ];
    }
}
