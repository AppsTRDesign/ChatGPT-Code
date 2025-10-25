<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Package;
use App\Models\Purchase;
use App\Models\Token;
use App\Models\User;
use App\Support\Session;

class AdminController extends Controller
{
    public function __construct(Session $session)
    {
        parent::__construct($session);
        $this->requireRole('admin');
    }

    public function dashboard(): string
    {
        $stats = [
            'members' => User::countActive(),
            'tokens' => Token::countAll(),
            'apiCalls' => Token::countApiCalls(),
            'pendingPurchases' => Purchase::countPending(),
            'approvedRevenue' => Purchase::sumApproved(),
            'failedTransactions' => Purchase::countFailed(),
        ];

        $trafficChart = [
            'type' => 'line',
            'data' => [
                'labels' => ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
                'datasets' => [
                    [
                        'label' => 'Trafik',
                        'data' => [140, 190, 160, 220, 260, 310, 280],
                        'borderColor' => '#0ba7c4',
                        'backgroundColor' => 'rgba(11,167,196,0.15)',
                        'tension' => 0.4,
                        'fill' => true,
                    ],
                    [
                        'label' => 'Üyelik',
                        'data' => [20, 32, 18, 25, 42, 35, 28],
                        'borderColor' => '#05668d',
                        'backgroundColor' => 'rgba(5,102,141,0.2)',
                        'tension' => 0.4,
                        'fill' => true,
                    ],
                ],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false],
        ];

        return $this->view('admin/dashboard', [
            'title' => 'Yönetim Paneli',
            'layout' => 'admin',
            'stats' => $stats,
            'trafficChart' => $trafficChart,
        ]);
    }

    public function members(): string
    {
        $members = User::all();
        return $this->view('admin/members', [
            'title' => 'Üyeler',
            'layout' => 'admin',
            'members' => $members,
        ]);
    }

    public function packages(): string
    {
        $packages = Package::all();
        return $this->view('admin/packages', [
            'title' => 'Paketler',
            'layout' => 'admin',
            'packages' => $packages,
        ]);
    }

    public function settings(): string
    {
        return $this->view('admin/settings', [
            'title' => 'Ayarlar',
            'layout' => 'admin',
        ]);
    }

    public function purchases(): string
    {
        $purchases = Purchase::all();
        return $this->view('admin/purchases', [
            'title' => 'Satın Alımlar',
            'layout' => 'admin',
            'purchases' => $purchases,
        ]);
    }

    public function notifications(): string
    {
        $notifications = Notification::all();
        return $this->view('admin/notifications', [
            'title' => 'Bildirimler',
            'layout' => 'admin',
            'notifications' => $notifications,
        ]);
    }

    public function apiUsage(): string
    {
        $usage = Token::apiUsageSummary();
        return $this->view('admin/api-usage', [
            'title' => 'API Raporları',
            'layout' => 'admin',
            'usage' => $usage,
        ]);
    }
}
