<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Package;
use App\Models\Site;
use App\Models\Token;
use App\Services\IyzicoGateway;
use App\Support\Session;

class ClientController extends Controller
{
    public function __construct(Session $session)
    {
        parent::__construct($session);
        $this->requireRole('member');
    }

    public function dashboard(): string
    {
        $user = $this->session->user();
        $notifications = Notification::forUser((int)$user['id']);
        $tokens = Token::forUser((int)$user['id']);
        $sites = Site::forUser((int)$user['id']);

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => ['Bugün', 'Dün', '3 Gün', '4 Gün', '5 Gün', '6 Gün', '7 Gün'],
                'datasets' => [
                    [
                        'label' => 'Gösterim',
                        'data' => [120, 132, 101, 134, 90, 230, 210],
                        'backgroundColor' => '#0ba7c4',
                    ],
                    [
                        'label' => 'Tıklama',
                        'data' => [45, 60, 40, 55, 35, 75, 80],
                        'backgroundColor' => '#05668d',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];

        return $this->view('client/dashboard', [
            'title' => 'Kontrol Paneli',
            'layout' => 'client',
            'notifications' => $notifications,
            'tokens' => $tokens,
            'sites' => $sites,
            'chartConfig' => $chartConfig,
        ]);
    }

    public function newNotification(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->session->user();
            Notification::create(["user_id" => (int)$user['id'],
                'title' => trim($_POST['title'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
                'link' => trim($_POST['link'] ?? ''),
            ]);
            $this->session->flash('success', 'Bildirim kuyruğa alındı.');
            header('Location: /app/notifications');
            return '';
        }

        $templates = Notification::templates();
        $user = $this->session->user();
        $sites = Site::forUser((int) $user['id']);
        return $this->view('client/new-notification', [
            'title' => 'Yeni Bildirim',
            'layout' => 'client',
            'templates' => $templates,
            'sites' => $sites,
        ]);
    }

    public function notifications(): string
    {
        $user = $this->session->user();
        $notifications = Notification::forUser((int)$user['id']);
        return $this->view('client/notifications', [
            'title' => 'Bildirimlerim',
            'layout' => 'client',
            'notifications' => $notifications,
        ]);
    }

    public function profile(): string
    {
        $user = $this->session->user();
        return $this->view('client/profile', [
            'title' => 'Profil',
            'layout' => 'client',
            'profile' => $user,
        ]);
    }

    public function packages(): string
    {
        $packages = Package::allActive();
        $gateway = new IyzicoGateway();
        $checkoutSamples = [];
        foreach ($packages as $package) {
            $checkoutSamples[$package['id']] = $gateway->initializeCheckout(
                'pkg-' . $package['id'],
                (float) $package['price'],
                base_url('payments/callback')
            );
        }

        return $this->view('client/packages', [
            'title' => 'Paket Satın Al',
            'layout' => 'client',
            'packages' => $packages,
            'checkoutSamples' => $checkoutSamples,
        ]);
    }

    public function tokens(): string
    {
        $user = $this->session->user();
        $tokens = Token::forUser((int)$user['id']);
        return $this->view('client/tokens', [
            'title' => 'API Anahtarları',
            'layout' => 'client',
            'tokens' => $tokens,
        ]);
    }

    public function sites(): string
    {
        $user = $this->session->user();
        $sites = Site::forUser((int)$user['id']);
        return $this->view('client/sites', [
            'title' => 'Sitelerim',
            'layout' => 'client',
            'sites' => $sites,
        ]);
    }

    public function apiUsage(): string
    {
        $user = $this->session->user();
        $usage = Token::usageForUser((int)$user['id']);
        return $this->view('client/api-usage', [
            'title' => 'API Kullanımı',
            'layout' => 'client',
            'usage' => $usage,
        ]);
    }

    public function apiGuide(): string
    {
        $user = $this->session->user();
        $tokens = Token::forUser((int)$user['id']);
        return $this->view('client/api-guide', [
            'title' => 'API Kullanım Kılavuzu',
            'layout' => 'client',
            'tokens' => $tokens,
        ]);
    }

    public function support(): string
    {
        return $this->view('client/support', [
            'title' => 'İletişim',
            'layout' => 'client',
        ]);
    }
}
