<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;

class DashboardService
{
    public static function getAdminStats(): array
    {
        $totalClients = Client::count();
        $activeTokens = Subscription::countActive();
        $pendingPurchases = Payment::pendingCount();
        $activePackages = ClientPackage::countActive();
        $totalNotifications = Notification::count();
        $totalApiCalls = self::countApiCalls();
        $blockedUsers = self::countBlockedUsers();
        $failedPayments = Payment::countByStatus('failed');
        $recentNewClients = Client::countNewSince(7);
        $recentRevenue = Payment::sumByStatus('paid', 7);

        return [
            'total_notifications' => $totalNotifications,
            'active_subscriptions' => $activeTokens,
            'template_count' => Template::count(),
            'total_clients' => $totalClients,
            'active_packages' => $activePackages,
            'pending_purchases' => $pendingPurchases,
            'approved_revenue' => Payment::sumByStatus('paid'),
            'total_api_calls' => $totalApiCalls,
            'blocked_users' => $blockedUsers,
            'failed_payments' => $failedPayments,
            'recent_new_clients' => $recentNewClients,
            'recent_revenue' => $recentRevenue
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

    protected static function countApiCalls(): int
    {
        $stmt = Database::pdo()->query('SELECT COUNT(*) FROM api_usage_logs');
        return (int) $stmt->fetchColumn();
    }

    protected static function countBlockedUsers(): int
    {
        $stmt = Database::pdo()->query('SELECT COUNT(*) FROM users WHERE is_blocked = 1');
        return (int) $stmt->fetchColumn();
    }
}
