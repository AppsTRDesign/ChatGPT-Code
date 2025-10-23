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
        body { background: #070f24; color: #f1f5f9; font-family: 'Poppins', Arial, sans-serif; margin: 0; padding: 0; }
        .wrapper { max-width: 640px; margin: 0 auto; padding: 32px 20px; }
        .card { background: linear-gradient(135deg, rgba(21, 36, 64, 0.96), rgba(10, 24, 44, 0.96)); border-radius: 22px; padding: 36px; box-shadow: 0 28px 72px rgba(8, 12, 24, 0.55); }
        .card h1 { margin-top: 0; font-size: 26px; color: rgba(248, 250, 252, 0.98); }
        .card p { color: rgba(226, 232, 240, 0.94); line-height: 1.65; font-size: 16px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; padding: 12px 28px; border-radius: 999px; text-decoration: none; font-weight: 600; letter-spacing: 0.02em; }
        .btn:hover { filter: brightness(1.05); }
        .footer { text-align: center; margin-top: 28px; font-size: 13px; color: rgba(186, 197, 223, 0.8); }
        a { color: #7dd3fc; }
        a:hover { color: #bae6fd; }
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
