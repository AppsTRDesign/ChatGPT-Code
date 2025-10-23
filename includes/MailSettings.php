<?php

namespace App;

class MailSettings
{
    public static function get(): array
    {
        $stmt = Helpers::db()->query('SELECT * FROM mail_settings LIMIT 1');
        $row = $stmt->fetch();
        return $row ?: [
            'is_active' => 0,
            'transport' => 'mail',
            'host' => null,
            'port' => null,
            'username' => null,
            'password' => null,
            'encryption' => 'none',
            'from_email' => null,
            'from_name' => null,
            'reply_to_email' => null,
        ];
    }

    public static function update(array $data): void
    {
        $exists = Helpers::db()->query('SELECT COUNT(*) FROM mail_settings')->fetchColumn();
        $payload = [
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'transport' => in_array($data['transport'] ?? 'mail', ['mail', 'smtp'], true) ? $data['transport'] : 'mail',
            'host' => $data['host'] ?? null,
            'port' => $data['port'] ?? null,
            'username' => $data['username'] ?? null,
            'password' => $data['password'] ?? null,
            'encryption' => in_array($data['encryption'] ?? 'none', ['none', 'ssl', 'tls'], true) ? $data['encryption'] : 'none',
            'from_email' => $data['from_email'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'reply_to_email' => $data['reply_to_email'] ?? null,
        ];

        if ($exists) {
            $sql = 'UPDATE mail_settings SET is_active = :is_active, transport = :transport, host = :host, port = :port, username = :username, password = :password, encryption = :encryption, from_email = :from_email, from_name = :from_name, reply_to_email = :reply_to_email, updated_at = NOW() LIMIT 1';
        } else {
            $sql = 'INSERT INTO mail_settings (is_active, transport, host, port, username, password, encryption, from_email, from_name, reply_to_email, updated_at) VALUES (:is_active, :transport, :host, :port, :username, :password, :encryption, :from_email, :from_name, :reply_to_email, NOW())';
        }

        $stmt = Helpers::db()->prepare($sql);
        $stmt->execute($payload);
    }
}
