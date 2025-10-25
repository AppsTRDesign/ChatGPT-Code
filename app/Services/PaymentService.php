<?php

namespace App\Services;

use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Model\CheckoutFormInitialize;

class PaymentService
{
    public static function createCheckout(array $payload): CheckoutFormInitialize
    {
        $options = new Options();
        $options->setApiKey($payload['api_key'] ?? '');
        $options->setSecretKey($payload['secret_key'] ?? '');
        $options->setBaseUrl($payload['base_url'] ?? 'https://api.iyzipay.com');

        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale($payload['locale'] ?? 'tr');
        $request->setConversationId($payload['conversation_id'] ?? uniqid('payment_', true));
        $request->setPrice($payload['price'] ?? '0.0');
        $request->setPaidPrice($payload['paid_price'] ?? '0.0');
        $request->setCurrency($payload['currency'] ?? 'TRY');
        $request->setBasketId($payload['basket_id'] ?? 'basket-1');
        $request->setCallbackUrl($payload['callback_url'] ?? '');
        $request->setEnabledInstallments($payload['installments'] ?? [2, 3, 6, 9]);

        return CheckoutFormInitialize::create($request, $options);
    }
}
