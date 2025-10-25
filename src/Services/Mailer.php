<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = getenv('SMTP_HOST') ?: 'smtp.example.com';
        $this->mailer->Port = (int) (getenv('SMTP_PORT') ?: 587);
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = getenv('SMTP_USER') ?: 'no-reply@example.com';
        $this->mailer->Password = getenv('SMTP_PASSWORD') ?: 'secret';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom($this->mailer->Username, 'NoaSoft WebPush');
    }

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML(true);

            return $this->mailer->send();
        } catch (MailerException $exception) {
            error_log('Mailer send error: ' . $exception->getMessage());
        }

        return false;
    }
}
