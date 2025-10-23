<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Mailer
{
    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $settings = MailSettings::get();
        $fromEmail = $settings['from_email'] ?: 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $fromName = $settings['from_name'] ?: Settings::siteName();

        if ((int) $settings['is_active'] === 1 && class_exists(PHPMailer::class)) {
            return self::sendViaPhpMailer($settings, $to, $subject, $body, $fromEmail, $fromName, $isHtml);
        }

        return self::sendViaMailFunction($to, $subject, $body, $fromEmail, $fromName, $isHtml);
    }

    private static function sendViaPhpMailer(array $settings, string $to, string $subject, string $body, string $fromEmail, string $fromName, bool $isHtml): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            if (($settings['transport'] ?? 'mail') === 'smtp') {
                $mail->isSMTP();
                $mail->Host = (string) $settings['host'];
                $mail->Port = (int) ($settings['port'] ?? 587);
                $mail->SMTPAuth = true;
                $mail->Username = (string) $settings['username'];
                $mail->Password = (string) $settings['password'];
                $encryption = $settings['encryption'] ?? 'tls';
                if ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($encryption === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            if (!empty($settings['reply_to_email'])) {
                $mail->addReplyTo($settings['reply_to_email'], $fromName);
            }

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $isHtml ? $body : nl2br($body);
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (MailException $e) {
            error_log('Mail gönderimi başarısız: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendViaMailFunction(string $to, string $subject, string $body, string $fromEmail, string $fromName, bool $isHtml): bool
    {
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8'),
        ];

        $message = $isHtml ? $body : wordwrap($body, 70);
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    public static function template(string $title, string $content): string
    {
        $year = date('Y');
        $site = Settings::siteName();
        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        body { background-color: #0b132b; color: #f8f9fa; font-family: 'Poppins', Arial, sans-serif; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 32px; }
        .card { background: #1c2541; border-radius: 16px; padding: 32px; box-shadow: 0 20px 45px rgba(13, 34, 64, 0.35); }
        .card h1 { margin-top: 0; font-size: 24px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #1d8cf8, #3358f4); color: #fff; padding: 12px 24px; border-radius: 999px; text-decoration: none; font-weight: 600; }
        .footer { text-align: center; margin-top: 24px; font-size: 12px; color: rgba(248, 249, 250, 0.6); }
        a { color: #1d8cf8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            {$content}
        </div>
        <div class="footer">&copy; {$year} {$site}</div>
    </div>
</body>
</html>
HTML;
    }
}
