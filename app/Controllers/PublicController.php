<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SupportRequest;

class PublicController extends Controller
{
    public function contact(): string
    {
        return $this->view('public/contact', [
            'title' => 'İletişim'
        ]);
    }

    public function apiGuide(): string
    {
        return $this->view('public/api-guide', [
            'title' => 'API Rehberi'
        ]);
    }

    public function apiDocs(): string
    {
        return $this->view('public/api-docs', [
            'title' => 'API Dokümantasyonu'
        ]);
    }

    public function submitContact(): string
    {
        $payload = $this->payload();
        $email = trim((string) ($payload['email'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? 'Genel Talep'));
        $message = trim((string) ($payload['message'] ?? ''));

        if ($email === '' || $message === '') {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lütfen e-posta ve mesaj alanlarını doldurun'
            ], 422);
        }

        SupportRequest::create([
            'client_id' => null,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ]);

        return $this->jsonResponse(['status' => 'success']);
    }
}
