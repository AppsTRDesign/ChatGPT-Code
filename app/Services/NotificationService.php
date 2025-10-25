<?php

namespace App\Services;

class NotificationService
{
    public static function dispatch(array $payload): array
    {
        // TODO: Validate API credentials and permissions
        // TODO: Queue notification jobs and persist Notification kayıtları

        $geo = isset($payload['ip_address']) ? GeoLocationService::locate($payload['ip_address']) : [];
        $device = isset($payload['user_agent']) ? DeviceService::parse($payload['user_agent']) : [];

        return [
            'status' => 'queued',
            'message' => 'Bildirim kuyruğa alındı',
            'payload' => $payload,
            'geo' => $geo,
            'device' => $device
        ];
    }

    public static function registerToken(array $payload): array
    {
        // TODO: Persist token and device metadata

        $geo = isset($payload['ip_address']) ? GeoLocationService::locate($payload['ip_address']) : [];
        $device = isset($payload['user_agent']) ? DeviceService::parse($payload['user_agent']) : [];

        return [
            'status' => 'success',
            'token' => $payload['token'] ?? null,
            'geo' => $geo,
            'device' => $device
        ];
    }
}
