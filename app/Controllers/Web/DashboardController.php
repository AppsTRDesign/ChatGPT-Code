<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\ViewRenderer;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DashboardController
{
    public function __construct(private readonly ViewRenderer $view)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $recentEvents = LicenseEvent::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $licenseCounts = License::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->get();
        $products = Product::all();

        $dailyValidations = LicenseEvent::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('event_type', 'validate')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        return $this->view->render($response, 'dashboard/index', [
            'recentEvents' => $recentEvents,
            'licenseCounts' => $licenseCounts,
            'products' => $products,
            'dailyValidations' => $dailyValidations,
            'user' => $this->currentUser(),
            'csrf_token' => $_SESSION['_csrf_token'] ?? '',
        ]);
    }

    private function currentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Kullanıcı',
            'role' => $_SESSION['user_role'] ?? 'developer',
        ];
    }
}
