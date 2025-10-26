<?php

namespace App\Models;

class ClientSite extends Model
{
    protected static string $table = 'client_sites';
    protected static array $fillable = [
        'client_id',
        'name',
        'domain',
        'api_identifier',
        'status',
        'description'
    ];

    public static function forClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId]);
    }

    public static function findByIdentifier(string $identifier): ?array
    {
        return static::first(['api_identifier' => $identifier]);
    }

    public static function activeForClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId, 'status' => 'active']);
    }

    public static function findForClientByDomain(int $clientId, string $domain): ?array
    {
        return static::first([
            'client_id' => $clientId,
            'domain' => $domain
        ]);
    }
}
