<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AudienceTemplate;
use App\Models\AudienceTemplateChannel;
use App\Models\ChannelTarget;
use App\Services\TelegramService;
use Throwable;

final class ChannelController extends Controller
{
    public function search(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $query = trim($_POST['query'] ?? '');
        $templateId = (int) ($_POST['template_id'] ?? 0);

        if ($accountId <= 0 || $query === '') {
            $this->json(['status' => 'error', 'message' => 'Hesap ve arama ifadesi zorunludur.'], 422);
        }

        $service = new TelegramService();

        try {
            $results = $service->searchChannels($accountId, $query, $templateId > 0 ? $templateId : null);
            $fragments = [
                '#channel-search-results' => $this->renderPartial('admin/partials/channel-search-results', [
                    'results' => $results,
                    'channelTemplates' => AudienceTemplate::forType('channel'),
                    'groupTemplates' => AudienceTemplate::forType('group'),
                ]),
                '#saved-channel-table' => $this->renderPartial('admin/partials/saved-channels-table', [
                    'channels' => ChannelTarget::allWithTemplates(),
                    'channelTemplates' => AudienceTemplate::forType('channel'),
                    'groupTemplates' => AudienceTemplate::forType('group'),
                    'channelAssignments' => AudienceTemplateChannel::templatesIndex(),
                ]),
            ];

            $this->json([
                'status' => 'success',
                'message' => sprintf('%d sonuç bulundu.', count($results)),
                'fragments' => $fragments,
            ]);
        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function assignTemplate(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $channel = ChannelTarget::find($id);
        if (!$channel) {
            $this->json(['status' => 'error', 'message' => 'Kanal kaydı bulunamadı.'], 404);
        }

        $templateId = (int) ($_POST['template_id'] ?? 0);
        $action = $_POST['action'] ?? 'attach';
        $template = $templateId > 0 ? AudienceTemplate::find($templateId) : null;

        if (!$template) {
            $this->json(['status' => 'error', 'message' => 'Şablon seçiniz.'], 422);
        }

        $expectedType = $channel['type'] === 'channel' ? 'channel' : 'group';
        if ($template['entity_type'] !== $expectedType) {
            $this->json(['status' => 'error', 'message' => 'Şablon türü ile kanal türü uyumsuz.'], 422);
        }

        if ($action === 'detach') {
            AudienceTemplateChannel::detach($templateId, $id);
        } else {
            AudienceTemplateChannel::attach($templateId, $id);
        }

        $this->json([
            'status' => 'success',
            'message' => 'Kanal şablonu güncellendi.',
            'fragments' => [
                '#saved-channel-table' => $this->renderPartial('admin/partials/saved-channels-table', [
                    'channels' => ChannelTarget::allWithTemplates(),
                    'channelTemplates' => AudienceTemplate::forType('channel'),
                    'groupTemplates' => AudienceTemplate::forType('group'),
                    'channelAssignments' => AudienceTemplateChannel::templatesIndex(),
                ]),
            ],
        ]);
    }

    private function renderPartial(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include resource_path('views/' . $view . '.php');
        return (string) ob_get_clean();
    }
}
