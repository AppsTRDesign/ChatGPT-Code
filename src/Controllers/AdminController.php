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

        return $this->view('admin/dashboard', [
            'title' => 'Yönetim Paneli',
            'stats' => $stats,
        ]);
    }

    public function members(): string
    {
        $members = User::all();
        return $this->view('admin/members', ['title' => 'Üyeler', 'members' => $members]);
    }

    public function packages(): string
    {
        $packages = Package::all();
        return $this->view('admin/packages', ['title' => 'Paketler', 'packages' => $packages]);
    }

    public function settings(): string
    {
        return $this->view('admin/settings', ['title' => 'Ayarlar']);
    }

    public function purchases(): string
    {
        $purchases = Purchase::all();
        return $this->view('admin/purchases', ['title' => 'Satın Alımlar', 'purchases' => $purchases]);
    }

    public function notifications(): string
    {
        $notifications = Notification::all();
        return $this->view('admin/notifications', ['title' => 'Bildirimler', 'notifications' => $notifications]);
    }

    public function apiUsage(): string
    {
        $usage = Token::apiUsageSummary();
        return $this->view('admin/api-usage', ['title' => 'API Raporları', 'usage' => $usage]);
    }
}
