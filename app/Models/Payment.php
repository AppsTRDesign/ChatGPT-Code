<?php

namespace App\Models;

class Payment extends Model
{
    protected static string $table = 'payments';
    protected static array $fillable = ['client_id', 'iyzico_payment_id', 'status', 'amount', 'currency', 'raw_response'];

    public static function record(int $clientId, array $payload): int
    {
        $data = [
            'client_id' => $clientId,
            'iyzico_payment_id' => $payload['iyzico_payment_id'] ?? ($payload['token'] ?? uniqid('iyzico_', false)),
            'status' => $payload['status'] ?? 'initiated',
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'TRY',
            'raw_response' => json_encode($payload)
        ];

        return static::create($data);
    }

    public static function allForClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId]);
    }
}
