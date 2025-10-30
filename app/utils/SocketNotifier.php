<?php
class SocketNotifier
{
    public static function notify(array $payload): void
    {
        $config = require __DIR__ . '/../config/config.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['api']['socket_server']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
