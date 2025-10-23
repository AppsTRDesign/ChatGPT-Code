<?php

namespace App;

use Iyzipay\Options;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;

class IyzicoService
{
    public static function isAvailable(): bool
    {
        $settings = Payment::settings();
        return (int) $settings['iyzico_enabled'] === 1
            && !empty($settings['iyzico_api_key'])
            && !empty($settings['iyzico_secret_key'])
            && class_exists(Options::class);
    }

    public static function initializeCheckout(array $user, array $package, int $purchaseId): array
    {
        if (!self::isAvailable()) {
            return ['success' => false, 'message' => 'İyzico ayarları eksik veya SDK yüklenmedi.'];
        }

        $options = self::options();
        if (!$options) {
            return ['success' => false, 'message' => 'İyzico seçenekleri yüklenemedi.'];
        }

        $request = new CreateCheckoutFormInitializeRequest();
        $price = number_format((float) $package['price'], 2, '.', '');
        $request->setLocale(Locale::TR);
        $request->setConversationId((string) $purchaseId);
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency(Currency::TL);
        $request->setBasketId('PURCHASE-' . $purchaseId);
        $request->setPaymentGroup(PaymentGroup::SUBSCRIPTION);
        $request->setCallbackUrl(rtrim(BASE_URL, '/') . '/client/iyzico-callback');
        $request->setEnabledInstallments([1, 2, 3, 6]);

        $buyer = new Buyer();
        $buyer->setId((string) $user['id']);
        $buyer->setName($user['username'] ?? 'Müşteri');
        $buyer->setSurname($user['username'] ?? 'Müşteri');
        $buyer->setGsmNumber($user['phone'] ?? '+900000000000');
        $buyer->setEmail($user['email']);
        $buyer->setIdentityNumber($user['identity'] ?? '11111111111');
        $buyer->setRegistrationDate($user['created_at'] ?? date('Y-m-d H:i:s'));
        $buyer->setLastLoginDate(date('Y-m-d H:i:s'));
        $buyer->setRegistrationAddress($user['address'] ?? 'Türkiye');
        $buyer->setIp($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $buyer->setCity($user['city'] ?? 'İstanbul');
        $buyer->setCountry('Turkey');
        $buyer->setZipCode($user['zip'] ?? '34000');
        $request->setBuyer($buyer);

        $address = new Address();
        $address->setContactName($user['username'] ?? 'Müşteri');
        $address->setCity($user['city'] ?? 'İstanbul');
        $address->setCountry('Turkey');
        $address->setAddress($user['address'] ?? 'İstanbul');
        $address->setZipCode($user['zip'] ?? '34000');
        $request->setShippingAddress($address);
        $request->setBillingAddress($address);

        $basketItem = new BasketItem();
        $basketItem->setId((string) $package['id']);
        $basketItem->setName($package['name']);
        $basketItem->setCategory1('QR Menü');
        $basketItem->setItemType(BasketItemType::VIRTUAL);
        $basketItem->setPrice($price);
        $request->setBasketItems([$basketItem]);

        $checkout = CheckoutFormInitialize::create($request, $options);
        if ($checkout->getStatus() !== 'success') {
            return ['success' => false, 'message' => $checkout->getErrorMessage() ?: 'Ödeme formu başlatılamadı.'];
        }

        self::storeTransaction($purchaseId, $checkout->getToken(), $checkout->getStatus(), json_encode($checkout->getRawResult(), JSON_UNESCAPED_UNICODE));

        return [
            'success' => true,
            'token' => $checkout->getToken(),
            'content' => $checkout->getCheckoutFormContent(),
            'paymentPageUrl' => $checkout->getPaymentPageUrl(),
        ];
    }

    public static function complete(string $token): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }

        $options = self::options();
        if (!$options) {
            return null;
        }

        $request = new RetrieveCheckoutFormRequest();
        $request->setToken($token);

        $result = CheckoutForm::retrieve($request, $options);
        return [
            'status' => $result->getPaymentStatus(),
            'price' => $result->getPrice(),
            'paidPrice' => $result->getPaidPrice(),
            'raw' => json_encode($result->getRawResult(), JSON_UNESCAPED_UNICODE),
            'conversationId' => $result->getConversationId(),
        ];
    }

    public static function storeTransaction(int $purchaseId, string $token, string $status, string $payload = ''): void
    {
        $stmt = Helpers::db()->prepare('INSERT INTO iyzico_transactions (user_package_id, iyzico_token, status, raw_response, updated_at) VALUES (:user_package_id, :token, :status, :payload, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), raw_response = VALUES(raw_response), updated_at = NOW()');
        $stmt->execute([
            'user_package_id' => $purchaseId,
            'token' => $token,
            'status' => $status,
            'payload' => $payload,
        ]);
    }

    private static function options(): ?Options
    {
        $settings = Payment::settings();
        if (empty($settings['iyzico_api_key']) || empty($settings['iyzico_secret_key'])) {
            return null;
        }

        $options = new Options();
        $options->setApiKey($settings['iyzico_api_key']);
        $options->setSecretKey($settings['iyzico_secret_key']);
        $options->setBaseUrl($settings['iyzico_base_url'] ?: 'https://sandbox-api.iyzipay.com');

        return $options;
    }
}
