<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AudienceTemplate;
use App\Models\AudienceTemplateChannel;
use App\Models\AudienceTemplateMember;
use App\Models\ChannelTarget;
use App\Models\Member;
use App\Models\TelegramAccount;
use App\Services\TelegramService;
use Throwable;

final class MemberController extends Controller
{
    public function index(): void
    {
        $memberTemplates = AudienceTemplate::forType('member');
        $channelTemplates = AudienceTemplate::forType('channel');
        $groupTemplates = AudienceTemplate::forType('group');
        $this->view('admin/members', [
            'title' => 'Üye Yönetimi',
            'members' => Member::all(),
            'accounts' => TelegramAccount::all(),
            'memberTemplates' => $memberTemplates,
            'memberTemplateCounts' => AudienceTemplate::countsByType('member'),
            'memberAssignments' => AudienceTemplateMember::templatesIndex(),
            'channelTemplates' => $channelTemplates,
            'groupTemplates' => $groupTemplates,
            'channelTemplateCounts' => AudienceTemplate::countsByType('channel'),
            'groupTemplateCounts' => AudienceTemplate::countsByType('group'),
            'savedChannels' => ChannelTarget::allWithTemplates(),
            'channelAssignments' => AudienceTemplateChannel::templatesIndex(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $data = [
            'telegram_id' => trim($_POST['telegram_id'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
            'joined_from_channel' => trim($_POST['joined_from_channel'] ?? ''),
            'last_active_at' => trim($_POST['last_active_at'] ?? ''),
            'online_status' => trim($_POST['online_status'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        Member::create($data);
        $this->json([
            'status' => 'success',
            'message' => 'Üye kaydedildi.',
            'reload' => true,
        ]);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        AudienceTemplateMember::deleteByMember($id);
        Member::delete($id);
        $this->json([
            'status' => 'success',
            'message' => 'Üye silindi.',
            'reload' => true,
        ]);
    }

    public function export(): void
    {
        $members = Member::all();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="members.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($members[0] ?? [
            'id' => 'ID',
            'telegram_id' => 'Telegram ID',
            'username' => 'Username',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'is_public' => 'Public',
            'joined_from_channel' => 'Joined From',
            'last_active_at' => 'Last Active',
            'online_status' => 'Online Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ]));

        foreach ($members as $member) {
            fputcsv($output, $member);
        }
        fclose($output);
        exit;
    }

    public function import(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['status' => 'error', 'message' => 'Dosya yüklenemedi.'], 400);
        }

        $handle = fopen($_FILES['file']['tmp_name'], 'rb');
        if (!$handle) {
            $this->json(['status' => 'error', 'message' => 'Dosya açılamadı.'], 400);
        }

        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            Member::create([
                'telegram_id' => $data['telegram_id'] ?? '',
                'username' => $data['username'] ?? '',
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'is_public' => isset($data['is_public']) ? (int) $data['is_public'] : 0,
                'joined_from_channel' => $data['joined_from_channel'] ?? '',
                'last_active_at' => $data['last_active_at'] ?? '',
                'online_status' => $data['online_status'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        fclose($handle);

        $this->json([
            'status' => 'success',
            'message' => 'Üyeler içe aktarıldı.',
            'reload' => true,
        ]);
    }

    public function discover(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $channel = trim($_POST['channel_username'] ?? '');
        $templateId = (int) ($_POST['template_id'] ?? 0);

        if ($accountId <= 0 || $channel === '') {
            $this->json(['status' => 'error', 'message' => 'Hesap ve kanal bilgisi gereklidir.'], 422);
        }

        $service = new TelegramService();

        try {
            $result = $service->queueMemberDiscovery($accountId, $channel, $templateId > 0 ? $templateId : null);
            $this->json([
                'status' => 'success',
                'discovered' => $result['discovered'] ?? 0,
                'message' => 'Üye keşfi kuyruğa alındı.',
                'reload' => false,
            ]);
        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function createTemplate(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $name = trim($_POST['name'] ?? '');
        $type = $_POST['entity_type'] ?? 'member';

        if ($name === '' || !in_array($type, ['member', 'channel', 'group'], true)) {
            $this->json(['status' => 'error', 'message' => 'Geçerli bir şablon adı ve türü seçiniz.'], 422);
        }

        if (AudienceTemplate::findByName($name, $type)) {
            $this->json(['status' => 'error', 'message' => 'Bu adda bir şablon zaten var.'], 409);
        }

        AudienceTemplate::create([
            'name' => $name,
            'entity_type' => $type,
            'description' => trim($_POST['description'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Şablon oluşturuldu.',
            'reload' => true,
        ]);
    }

    public function deleteTemplate(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        AudienceTemplate::deleteWithRelations($id);

        $this->json([
            'status' => 'success',
            'message' => 'Şablon kaldırıldı.',
            'reload' => true,
        ]);
    }

    public function assignTemplate(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $member = Member::find($id);
        if (!$member) {
            $this->json(['status' => 'error', 'message' => 'Üye bulunamadı.'], 404);
        }

        $templateId = (int) ($_POST['template_id'] ?? 0);
        $action = $_POST['action'] ?? 'attach';

        if ($templateId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Şablon seçiniz.'], 422);
        }

        if ($action === 'detach') {
            AudienceTemplateMember::detach($templateId, $id);
        } else {
            AudienceTemplateMember::attach($templateId, $id);
        }

        $this->json([
            'status' => 'success',
            'message' => 'Şablon bağlantısı güncellendi.',
            'reload' => true,
        ]);
    }
}
