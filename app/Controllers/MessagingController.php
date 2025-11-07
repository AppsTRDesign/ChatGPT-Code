<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AudienceTemplate;
use App\Models\AudienceTemplateChannel;
use App\Models\AudienceTemplateMember;
use App\Models\ChannelTarget;
use App\Models\DispatchJob;
use App\Models\MessageTemplate;

final class MessagingController extends Controller
{
    public function index(): void
    {
        $this->view('admin/messaging', [
            'title' => 'Mesaj Gönderimleri',
            'jobs' => DispatchJob::all(),
            'templates' => MessageTemplate::all(),
            'memberTemplates' => AudienceTemplate::forType('member'),
            'channelTemplates' => AudienceTemplate::forType('channel'),
            'groupTemplates' => AudienceTemplate::forType('group'),
            'channelTargets' => ChannelTarget::allWithTemplates(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $mode = $_POST['mode'] ?? 'message';

        if ($mode === 'invitation') {
            $this->queueInvitations();
            return;
        }

        $this->queueMessages();
    }

    public function update(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        DispatchJob::update($id, [
            'status' => $_POST['status'] ?? 'queued',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Gönderim güncellendi.',
            'reload' => true,
        ]);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        DispatchJob::delete($id);
        $this->json([
            'status' => 'success',
            'message' => 'Gönderim silindi.',
            'reload' => true,
        ]);
    }

    private function queueMessages(): void
    {
        $name = trim($_POST['name'] ?? '');
        $templateId = (int) ($_POST['template_id'] ?? 0);
        $scheduledFor = trim($_POST['scheduled_for'] ?? '');
        $sourceType = $_POST['source_type'] ?? 'manual';
        $createdBy = auth_user()['id'] ?? 0;

        if ($name === '' || $templateId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Kampanya adı ve mesaj şablonu zorunludur.'], 422);
        }

        $scheduled = $scheduledFor !== '' ? $scheduledFor : null;
        $now = date('Y-m-d H:i:s');
        $queued = 0;

        if ($sourceType === 'manual') {
            $targetType = trim($_POST['target_type'] ?? '');
            $targetValue = trim($_POST['target_value'] ?? '');

            if ($targetType === '' || $targetValue === '') {
                $this->json(['status' => 'error', 'message' => 'Hedef türü ve değeri gereklidir.'], 422);
            }

            DispatchJob::create([
                'name' => $name,
                'action' => 'send_message',
                'template_id' => $templateId,
                'target_type' => $targetType,
                'target_value' => $targetValue,
                'metadata' => null,
                'scheduled_for' => $scheduled,
                'status' => 'queued',
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $queued = 1;
        } elseif ($sourceType === 'member_template') {
            $memberTemplateId = (int) ($_POST['member_template_id'] ?? 0);
            if ($memberTemplateId <= 0) {
                $this->json(['status' => 'error', 'message' => 'Üye şablonu seçiniz.'], 422);
            }

            $memberTemplate = AudienceTemplate::find($memberTemplateId);
            if (!$memberTemplate || $memberTemplate['entity_type'] !== 'member') {
                $this->json(['status' => 'error', 'message' => 'Geçersiz üye şablonu.'], 422);
            }

            $members = AudienceTemplateMember::membersForTemplate($memberTemplateId);
            if (!$members) {
                $this->json(['status' => 'error', 'message' => 'Seçilen üye şablonunda kayıt bulunamadı.'], 422);
            }

            foreach ($members as $member) {
                $username = $member['username'] ? '@' . ltrim((string) $member['username'], '@') : '';
                $targetValue = $username !== '' ? $username : (string) $member['telegram_id'];
                $metadata = [
                    'member_id' => (int) $member['id'],
                    'member' => $member,
                ];

                DispatchJob::create([
                    'name' => $name,
                    'action' => 'send_message',
                    'template_id' => $templateId,
                    'target_type' => 'direct',
                    'target_value' => $targetValue,
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                    'scheduled_for' => $scheduled,
                    'status' => 'queued',
                    'created_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $queued++;
            }
        } elseif (in_array($sourceType, ['channel_template', 'group_template'], true)) {
            $channelTemplateId = (int) ($_POST['channel_template_id'] ?? 0);
            if ($channelTemplateId <= 0) {
                $this->json(['status' => 'error', 'message' => 'Kanal veya grup şablonu seçiniz.'], 422);
            }

            $channelTemplate = AudienceTemplate::find($channelTemplateId);
            if (!$channelTemplate) {
                $this->json(['status' => 'error', 'message' => 'Şablon bulunamadı.'], 422);
            }

            if ($sourceType === 'channel_template' && $channelTemplate['entity_type'] !== 'channel') {
                $this->json(['status' => 'error', 'message' => 'Seçilen şablon kanal türüyle uyumlu değil.'], 422);
            }

            if ($sourceType === 'group_template' && $channelTemplate['entity_type'] !== 'group') {
                $this->json(['status' => 'error', 'message' => 'Seçilen şablon grup türüyle uyumlu değil.'], 422);
            }

            $channels = AudienceTemplateChannel::channelsForTemplate($channelTemplateId);
            if (!$channels) {
                $this->json(['status' => 'error', 'message' => 'Seçilen şablonda kanal/grup kaydı yok.'], 422);
            }

            foreach ($channels as $channel) {
                $targetType = $channel['type'] ?? ($sourceType === 'group_template' ? 'group' : 'channel');
                $targetValue = $channel['username'] ? '@' . ltrim((string) $channel['username'], '@') : (string) $channel['telegram_id'];
                $metadata = [
                    'channel_id' => (int) $channel['id'],
                    'channel' => $channel,
                ];

                DispatchJob::create([
                    'name' => $name,
                    'action' => 'send_message',
                    'template_id' => $templateId,
                    'target_type' => $targetType,
                    'target_value' => $targetValue,
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                    'scheduled_for' => $scheduled,
                    'status' => 'queued',
                    'created_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $queued++;
            }
        } else {
            $this->json(['status' => 'error', 'message' => 'Geçersiz gönderim kaynağı.'], 422);
        }

        $this->json([
            'status' => 'success',
            'message' => sprintf('%d hedef için gönderim kuyruğa alındı.', $queued),
            'reload' => true,
        ]);
    }

    private function queueInvitations(): void
    {
        $name = trim($_POST['invite_name'] ?? ($_POST['name'] ?? ''));
        $memberTemplateId = (int) ($_POST['invite_member_template_id'] ?? $_POST['member_template_id'] ?? 0);
        $channelId = (int) ($_POST['channel_id'] ?? 0);
        $createdBy = auth_user()['id'] ?? 0;

        if ($name === '' || $memberTemplateId <= 0 || $channelId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Davet adı, üye şablonu ve hedef kanal seçiniz.'], 422);
        }

        $members = AudienceTemplateMember::membersForTemplate($memberTemplateId);
        if (!$members) {
            $this->json(['status' => 'error', 'message' => 'Seçilen üye şablonunda kayıt yok.'], 422);
        }

        $template = AudienceTemplate::find($memberTemplateId);
        if (!$template || $template['entity_type'] !== 'member') {
            $this->json(['status' => 'error', 'message' => 'Geçerli bir üye şablonu seçiniz.'], 422);
        }

        $channel = ChannelTarget::find($channelId);
        if (!$channel) {
            $this->json(['status' => 'error', 'message' => 'Kanal kaydı bulunamadı.'], 404);
        }

        $now = date('Y-m-d H:i:s');
        $queued = 0;

        foreach ($members as $member) {
            $metadata = [
                'member_id' => (int) $member['id'],
                'member' => $member,
                'channel_id' => (int) $channel['id'],
                'channel' => $channel,
            ];

            $targetValue = $channel['username'] ? '@' . ltrim((string) $channel['username'], '@') : (string) $channel['telegram_id'];

            DispatchJob::create([
                'name' => $name,
                'action' => 'invite_members',
                'template_id' => null,
                'target_type' => $channel['type'] ?? 'channel',
                'target_value' => $targetValue,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                'scheduled_for' => null,
                'status' => 'queued',
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $queued++;
        }

        $this->json([
            'status' => 'success',
            'message' => sprintf('%d üye için davet kuyruğa alındı.', $queued),
            'reload' => true,
        ]);
    }
}
