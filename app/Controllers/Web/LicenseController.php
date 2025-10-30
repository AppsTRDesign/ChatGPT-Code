<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\ViewRenderer;
use App\Models\License;
use App\Services\LicenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LicenseController
{
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly LicenseService $licenseService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $licenses = License::query()
            ->with('product')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $this->view->render($response, 'licenses/index', [
            'licenses' => $licenses,
            'csrf_token' => $_SESSION['_csrf_token'] ?? '',
            'user' => $this->currentUser(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'GET') {
            return $this->view->render($response, 'licenses/create', [
                'csrf_token' => $_SESSION['_csrf_token'] ?? '',
                'user' => $this->currentUser(),
            ]);
        }

        $data = $request->getParsedBody();
        $payload = [
            'product_code' => $data['product_code'] ?? '',
            'type' => $data['type'] ?? 'perpetual',
            'seats' => (int) ($data['seats'] ?? 1),
            'expires_at' => $data['expires_at'] ?? null,
            'grace_days' => (int) ($data['grace_days'] ?? 3),
        ];

        $result = $this->licenseService->issue($payload, null, null, $this->clientIp($request), $request->getHeaderLine('User-Agent'));
        $_SESSION['flash'] = 'Lisans oluşturuldu: ' . $result['key_display'];

        return $response->withHeader('Location', '/licenses')->withStatus(302);
    }

    private function clientIp(Request $request): string
    {
        return $request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0';
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
