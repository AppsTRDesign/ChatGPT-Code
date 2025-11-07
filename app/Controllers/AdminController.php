<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\Service;
use App\Models\TelegramAccount;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        $stats = [
            'total_accounts' => count(TelegramAccount::all()),
            'total_members' => count(Member::all()),
            'active_templates' => count(MessageTemplate::all()),
        ];

        $this->view('admin/dashboard', [
            'title' => 'Kontrol Paneli',
            'stats' => $stats,
            'services' => Service::all(),
        ]);
    }
}
