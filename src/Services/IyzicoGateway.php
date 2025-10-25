<?php

declare(strict_types=1);

namespace App\Services;

use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;

class IyzicoGateway
{
    private Options $options;

    public function __construct()
    {
        $this->options = new Options();
        $this->options->setApiKey(getenv('IYZICO_API_KEY') ?: 'sandbox-api-key');
        $this->options->setSecretKey(getenv('IYZICO_SECRET_KEY') ?: 'sandbox-secret');
        $this->options->setBaseUrl(getenv('IYZICO_BASE_URL') ?: 'https://sandbox-api.iyzipay.com');
    }

    public function initializeCheckout(string $conversationId, float $price, string $callbackUrl): array
    {
        $request = new CreateCheckoutFormInitializeRequest();
        $request->setConversationId($conversationId);
        $request->setPrice((string) $price);
        $request->setPaidPrice((string) $price);
        $request->setCurrency('TRY');
        $request->setCallbackUrl($callbackUrl);
        $request->setBasketId('B' . $conversationId);
        $request->setLocale('tr');

        return [
            'conversationId' => $conversationId,
            'price' => $request->getPrice(),
            'callbackUrl' => $callbackUrl,
            'basketId' => $request->getBasketId(),
            'locale' => $request->getLocale(),
            'apiKey' => $this->options->getApiKey(),
        ];
    }
}
