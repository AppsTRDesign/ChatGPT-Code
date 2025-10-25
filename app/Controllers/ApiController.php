<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\NotificationService;

class ApiController extends Controller
{
    public function dispatchNotification(): string
    {
        $payload = $this->request->json();
        $response = NotificationService::dispatch($payload);

        $status = $response['status'] === 'queued' ? 200 : 400;
        return $this->jsonResponse($response, $status);
    }

    public function registerToken(): string
    {
        $payload = $this->request->json();
        $response = NotificationService::registerToken($payload);

        $status = $response['status'] === 'success' ? 200 : 400;
        return $this->jsonResponse($response, $status);
    }

    public function inbox(): string
    {
        $payload = $this->request->json();
        $response = NotificationService::pullInbox($payload);

        $status = $response['status'] === 'success' ? 200 : 400;
        return $this->jsonResponse($response, $status);
    }

    public function receipt(): string
    {
        $payload = $this->request->json();
        $response = NotificationService::registerReceipt($payload);

        $status = $response['status'] === 'success' ? 200 : 400;
        return $this->jsonResponse($response, $status);
    }
}
