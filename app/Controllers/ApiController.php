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

        header('Content-Type: application/json');
        return json_encode($response);
    }

    public function registerToken(): string
    {
        $payload = $this->request->json();
        $response = NotificationService::registerToken($payload);

        header('Content-Type: application/json');
        return json_encode($response);
    }
}
