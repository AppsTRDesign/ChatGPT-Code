<?php

namespace App\Controllers;

use App\Models\Subscriber;

class ClientController
{
    public function landing(): void
    {
        view('client/landing', ['title' => 'WebPush Platformu']);
    }

    public function registerSubscriber(): void
    {
        header('Content-Type: application/json');

        if (!is_post()) {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Yalnızca POST istekleri desteklenir.']);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];

        $endpoint = trim($payload['endpoint'] ?? '');
        if ($endpoint === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Endpoint zorunludur.']);
            return;
        }

        $subscriberModel = new Subscriber();
        if ($subscriberModel->findByEndpoint($endpoint)) {
            echo json_encode(['success' => true, 'message' => 'Abone zaten kayıtlı.']);
            return;
        }

        $subscriberModel->create([
            'endpoint' => $endpoint,
            'device' => $payload['device'] ?? 'web',
            'browser' => $payload['browser'] ?? 'unknown',
            'timezone' => $payload['timezone'] ?? 'UTC',
            'tags' => isset($payload['tags']) ? implode(',', (array) $payload['tags']) : null,
        ]);

        echo json_encode(['success' => true, 'message' => 'Abonelik oluşturuldu.']);
    }
}
