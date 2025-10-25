<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Subscription;
use App\Models\Template;

class DashboardService
{
    public static function getAdminStats(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'active_subscriptions' => Subscription::countActive(),
            'template_count' => Template::count()
        ];
    }

    public static function getClientStats(): array
    {
        return [
            'sent_notifications' => Notification::countByCurrentClient(),
            'active_tokens' => Subscription::countActiveByCurrentClient(),
            'available_templates' => Template::countActive()
        ];
    }
}
