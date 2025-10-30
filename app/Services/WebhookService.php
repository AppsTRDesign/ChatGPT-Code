<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\Product;
use App\Models\Webhook;

final class WebhookService
{
    public function dispatch(Product $product, string $eventType, array $payload): void
    {
        $webhooks = $product->webhooks()->where('is_active', true)->get();

        foreach ($webhooks as $webhook) {
            $this->postWebhook($webhook, $eventType, $payload);
        }
    }

    private function postWebhook(Webhook $webhook, string $eventType, array $payload): void
    {
        $body = json_encode([
            'event' => $eventType,
            'data' => $payload,
            'sent_at' => gmdate('c'),
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $body, $webhook->secret);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Signature: {$signature}",
                'content' => $body,
                'timeout' => 5,
            ],
        ];

        @file_get_contents($webhook->url, false, stream_context_create($options));
    }
}
