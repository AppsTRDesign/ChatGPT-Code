<?php

namespace Helpers;

class Mail
{
    public static function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $to = trim(strtolower($to));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $fromEmail = static::sanitizeEmail($options['from_email'] ?? '') ?: static::defaultFromEmail();
        $fromName = trim((string)($options['from_name'] ?? 'QR Menü')) ?: 'QR Menü';
        $replyEmail = static::sanitizeEmail($options['reply_to'] ?? '') ?: $fromEmail;
        $replyName = trim((string)($options['reply_name'] ?? $fromName)) ?: $fromName;

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . static::formatAddress($fromEmail, $fromName),
            'Reply-To: ' . static::formatAddress($replyEmail, $replyName),
        ];

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        return mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    }

    private static function sanitizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(strtolower($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private static function defaultFromEmail(): string
    {
        $host = parse_url(BASE_URL ?? '', PHP_URL_HOST) ?: 'qrmenu.local';
        return 'no-reply@' . ltrim($host, '@');
    }

    private static function formatAddress(string $email, string $name): string
    {
        $safeName = addcslashes($name, "\"\\");
        return sprintf('"%s" <%s>', $safeName, $email);
    }
}
