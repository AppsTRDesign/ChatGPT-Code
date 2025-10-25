<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    public static function send(array $payload): bool
    {
        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $payload['host'] ?? 'smtp.example.com';
            $mailer->SMTPAuth = true;
            $mailer->Username = $payload['username'] ?? '';
            $mailer->Password = $payload['password'] ?? '';
            $mailer->SMTPSecure = $payload['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = $payload['port'] ?? 587;

            $mailer->setFrom($payload['from_email'] ?? 'noreply@example.com', $payload['from_name'] ?? 'NoaSoft Web Push');
            $mailer->addAddress($payload['to_email'] ?? 'user@example.com');

            $mailer->isHTML(true);
            $mailer->Subject = $payload['subject'] ?? 'Bildirim';
            $mailer->Body = $payload['body'] ?? '';

            $mailer->send();
            return true;
        } catch (Exception $exception) {
            error_log('Mail gönderilemedi: ' . $exception->getMessage());
            return false;
        }
    }
}
