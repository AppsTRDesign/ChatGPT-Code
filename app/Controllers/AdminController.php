<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DashboardService;

class AdminController extends Controller
{
    public function dashboard(): string
    {
        $stats = DashboardService::getAdminStats();

        return $this->view('admin/dashboard/index', [
            'title' => 'Yönetim Paneli',
            'stats' => $stats
        ]);
    }
}
