<?php

namespace App;

class Payment
{
    public static function settings(): array
    {
        $stmt = Helpers::db()->query('SELECT * FROM payment_settings LIMIT 1');
        $settings = $stmt->fetch();
        if ($settings) {
            $settings['iyzico_base_url'] = $settings['iyzico_base_url'] ?: 'https://sandbox-api.iyzipay.com';
        }
        return $settings ?: [
            'iyzico_enabled' => 0,
            'iyzico_api_key' => '',
            'iyzico_secret_key' => '',
            'iyzico_base_url' => 'https://sandbox-api.iyzipay.com',
            'bank_account' => '',
            'bank_enabled' => 1,
        ];
    }

    public static function update(array $data): bool
    {
        $payload = [
            'iyzico_enabled' => (int) $data['iyzico_enabled'],
            'iyzico_api_key' => $data['iyzico_api_key'],
            'iyzico_secret_key' => $data['iyzico_secret_key'],
            'iyzico_base_url' => $data['iyzico_base_url'],
            'bank_account' => $data['bank_account'],
            'bank_enabled' => (int) $data['bank_enabled'],
        ];

        $exists = Helpers::db()->query('SELECT COUNT(*) FROM payment_settings')->fetchColumn();
        if ($exists) {
            $stmt = Helpers::db()->prepare('UPDATE payment_settings SET iyzico_enabled = :iyzico_enabled, iyzico_api_key = :iyzico_api_key, iyzico_secret_key = :iyzico_secret_key, iyzico_base_url = :iyzico_base_url, bank_account = :bank_account, bank_enabled = :bank_enabled, updated_at = NOW()');
            return $stmt->execute($payload);
        }

        $stmt = Helpers::db()->prepare('INSERT INTO payment_settings (iyzico_enabled, iyzico_api_key, iyzico_secret_key, iyzico_base_url, bank_account, bank_enabled) VALUES (:iyzico_enabled, :iyzico_api_key, :iyzico_secret_key, :iyzico_base_url, :bank_account, :bank_enabled)');
        return $stmt->execute($payload);
    }
}
