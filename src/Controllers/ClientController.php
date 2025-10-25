<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Package;
use App\Models\Site;
use App\Models\Token;
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

        return $this->view('client/dashboard', [
            'title' => 'Kontrol Paneli',
            'notifications' => $notifications,
            'tokens' => $tokens,
            'sites' => $sites,
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
        return $this->view('client/new-notification', ['title' => 'Yeni Bildirim', 'templates' => $templates]);
    }

    public function notifications(): string
    {
        $user = $this->session->user();
        $notifications = Notification::forUser((int)$user['id']);
        return $this->view('client/notifications', ['title' => 'Bildirimlerim', 'notifications' => $notifications]);
    }

    public function profile(): string
    {
        $user = $this->session->user();
        return $this->view('client/profile', ['title' => 'Profil', 'profile' => $user]);
    }

    public function packages(): string
    {
        $packages = Package::allActive();
        return $this->view('client/packages', ['title' => 'Paket Satın Al', 'packages' => $packages]);
    }

    public function tokens(): string
    {
        $user = $this->session->user();
        $tokens = Token::forUser((int)$user['id']);
        return $this->view('client/tokens', ['title' => 'API Anahtarları', 'tokens' => $tokens]);
    }

    public function sites(): string
    {
        $user = $this->session->user();
        $sites = Site::forUser((int)$user['id']);
        return $this->view('client/sites', ['title' => 'Sitelerim', 'sites' => $sites]);
    }

    public function apiUsage(): string
    {
        $user = $this->session->user();
        $usage = Token::usageForUser((int)$user['id']);
        return $this->view('client/api-usage', ['title' => 'API Kullanımı', 'usage' => $usage]);
    }

    public function apiGuide(): string
    {
        $user = $this->session->user();
        $tokens = Token::forUser((int)$user['id']);
        return $this->view('client/api-guide', [
            'title' => 'API Kullanım Kılavuzu',
            'tokens' => $tokens,
        ]);
    }

    public function support(): string
    {
        return $this->view('client/support', ['title' => 'İletişim']);
    }
}
