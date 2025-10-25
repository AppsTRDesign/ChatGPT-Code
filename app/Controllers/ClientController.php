<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DashboardService;

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
        $stats = DashboardService::getClientStats();

        return $this->view('client/dashboard/index', [
            'title' => 'Müşteri Paneli',
            'stats' => $stats
        ]);
    }
}
