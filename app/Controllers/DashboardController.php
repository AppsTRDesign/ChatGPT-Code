<?php

namespace App\Controllers;

use App\Models\Campaign;
use App\Models\NotificationLog;
use App\Models\Segment;
use App\Models\Subscriber;
use App\Services\NotificationService;

class DashboardController
{
    public function index(): void
    {
        $subscribers = (new Subscriber())->all();
        $segments = (new Segment())->all();
        $campaigns = (new Campaign())->all();
        $logs = (new NotificationLog())->recent();

        view('admin/dashboard', [
            'title' => 'Kontrol Paneli',
            'subscribers' => $subscribers,
            'segments' => $segments,
            'campaigns' => $campaigns,
            'logs' => $logs,
        ]);
    }

    public function createSegment(): void
    {
        if (!is_post()) {
            redirect('admin/dashboard');
        }

        $name = trim($_POST['name'] ?? '');
        $filters = trim($_POST['filters'] ?? '') ?: null;

        if ($name === '') {
            $_SESSION['flash_error'] = 'Segment adı zorunludur.';
            redirect('admin/dashboard');
        }

        (new Segment())->create($name, $filters);
        $_SESSION['flash_success'] = 'Segment başarıyla oluşturuldu.';
        redirect('admin/dashboard');
    }

    public function createCampaign(): void
    {
        if (!is_post()) {
            redirect('admin/dashboard');
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
            'target_segment' => $_POST['target_segment'] ?? null,
            'target_tags' => trim($_POST['target_tags'] ?? '') ?: null,
        ];

        if ($data['title'] === '' || $data['message'] === '') {
            $_SESSION['flash_error'] = 'Başlık ve mesaj alanları zorunludur.';
            redirect('admin/dashboard');
        }

        $campaignModel = new Campaign();
        $campaignId = $campaignModel->create($data);

        $service = new NotificationService();
        $sent = $service->dispatchCampaign($campaignId, $data);

        $_SESSION['flash_success'] = sprintf('Kampanya gönderimi tamamlandı. %d aboneye ulaşıldı.', $sent);

        redirect('admin/dashboard');
    }
}
