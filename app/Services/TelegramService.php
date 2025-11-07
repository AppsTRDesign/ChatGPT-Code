<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\AudienceTemplateChannel;
use App\Models\AudienceTemplateMember;
use App\Models\ChannelTarget;
use App\Models\DispatchJob;
use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use RuntimeException;
use Throwable;

final class TelegramService
{
    public function getApiCredentials(): array
    {
        $apiId = Setting::get('telegram_api_id');
        $apiHash = Setting::get('telegram_api_hash');

        if ($apiId === null || $apiId === '') {
            $apiId = (string) Config::get('telegram.api_id', '');
        }

        if ($apiHash === null || $apiHash === '') {
            $apiHash = (string) Config::get('telegram.api_hash', '');
        }

        return [
            'api_id' => $apiId,
            'api_hash' => $apiHash,
        ];
    }

    public function sendLoginCode(int $accountId): string
    {
        $account = $this->getAccount($accountId);

        try {
            $client = $this->buildClient($account, false);
            $hash = $client->phoneLogin($account['phone_number']);

            TelegramAccount::update($accountId, [
                'session_status' => 'code_sent',
                'phone_code_hash' => $hash,
                'two_factor_hint' => null,
                'last_error' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return (string) $hash;
        } catch (Throwable $e) {
            $this->flagAccountError($accountId, $e);
            throw $e;
        }
    }

    public function completeLogin(int $accountId, string $code, ?string $twoFactorPassword = null): string
    {
        $account = $this->getAccount($accountId);

        try {
            $client = $this->buildClient($account, false);
            $result = $client->completePhoneLogin($code);

            if (is_array($result) && ($result['_'] ?? null) === 'account.password') {
                TelegramAccount::update($accountId, [
                    'session_status' => '2fa_required',
                    'two_factor_hint' => $result['hint'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                if ($twoFactorPassword === null || $twoFactorPassword === '') {
                    return '2fa_required';
                }

                $client->complete2faLogin($twoFactorPassword);
            }

            $client->start();

            TelegramAccount::update($accountId, [
                'session_status' => 'ready',
                'phone_code_hash' => null,
                'two_factor_hint' => null,
                'last_error' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return 'ready';
        } catch (Throwable $e) {
            $this->flagAccountError($accountId, $e);
            throw $e;
        }
    }

    public function logout(int $accountId): void
    {
        $account = $this->getAccount($accountId);

        try {
            $client = $this->buildClient($account);
            $client->logout();
            TelegramAccount::update($accountId, [
                'session_status' => 'logged_out',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            $this->flagAccountError($accountId, $e);
            throw $e;
        }
    }

    public function deleteSession(int $accountId): void
    {
        $sessionPath = $this->getSessionPath($accountId);
        if (file_exists($sessionPath)) {
            unlink($sessionPath);
        }
    }

    public function runDispatchJob(array $job, int $accountId): void
    {
        $account = $this->getAccount($accountId);
        try {
            $client = $this->buildClient($account);
            $metadata = [];
            if (isset($job['metadata']) && $job['metadata'] !== null && $job['metadata'] !== '') {
                try {
                    $metadata = json_decode((string) $job['metadata'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    $metadata = [];
                }
            }

            $action = $job['action'] ?? 'send_message';

            if ($action === 'invite_members') {
                $this->runInviteJob($client, $job, is_array($metadata) ? $metadata : []);
                $this->sleepFor('rate_limit_invite_delay_ms', 2000);
            } else {
                $this->runSendMessageJob($client, $job, is_array($metadata) ? $metadata : []);
                $this->sleepFor('rate_limit_message_delay_ms', 1200);
            }
        } catch (Throwable $e) {
            $this->flagAccountError($accountId, $e);
            throw $e;
        }
    }

    private function runSendMessageJob(API $client, array $job, array $metadata): void
    {
        $template = MessageTemplate::find((int) $job['template_id']);
        if (!$template) {
            throw new RuntimeException('Mesaj şablonu bulunamadı.');
        }

        $peer = $this->resolvePeer(
            $client,
            (string) ($job['target_type'] ?? ''),
            (string) ($job['target_value'] ?? ''),
            $metadata
        );

        $this->sendTemplateMessage($client, $template, $peer, $job['scheduled_for'] ?? null);
    }

    private function runInviteJob(API $client, array $job, array $metadata): void
    {
        $memberId = (int) ($metadata['member_id'] ?? 0);
        $channelId = (int) ($metadata['channel_id'] ?? 0);

        if ($memberId <= 0 || $channelId <= 0) {
            throw new RuntimeException('Davet işlemi için üye ve kanal bilgisi gereklidir.');
        }

        $member = Member::find($memberId);
        $channel = ChannelTarget::find($channelId);

        if (!$member || !$channel) {
            throw new RuntimeException('Davet için gerekli kayıtlar bulunamadı.');
        }

        $channelPeer = $this->resolveChannelPeer($client, ['channel' => $channel], (string) ($job['target_value'] ?? ''));
        $memberPeer = $this->resolvePeer(
            $client,
            'direct',
            $member['username'] ? '@' . ltrim((string) $member['username'], '@') : (string) $member['telegram_id'],
            ['member' => $member]
        );

        $channelInput = $client->getInputEntity($channelPeer);
        $memberInput = $client->getInputEntity($memberPeer);

        if (in_array($channel['type'], ['channel', 'group'], true)) {
            $client->channels->inviteToChannel([
                'channel' => $channelInput,
                'users' => [$memberInput],
            ]);
            return;
        }

        $client->messages->addChatUser([
            'chat_id' => (int) $channel['telegram_id'],
            'user_id' => $memberInput,
            'fwd_limit' => 10,
        ]);
    }

    public function queueMemberDiscovery(int $accountId, string $channelUsername, ?int $templateId = null): array
    {
        $account = $this->getAccount($accountId);
        $username = ltrim($channelUsername, '@');
        $discovered = 0;

        try {
            $client = $this->buildClient($account);
            $peer = $client->getPwrChat($username);
            $offset = 0;
            $limit = 100;

            do {
                $response = $client->messages->getParticipants([
                    'channel' => $peer,
                    'filter' => ['_' => 'channelParticipantsRecent'],
                    'offset' => $offset,
                    'limit' => $limit,
                    'hash' => 0,
                ]);

                $users = [];
                foreach ($response['users'] ?? [] as $user) {
                    $users[$user['id']] = $user;
                }

                $participants = $response['participants'] ?? [];
                if (!$participants) {
                    break;
                }

                foreach ($participants as $participant) {
                    $user = $users[$participant['user_id']] ?? null;
                    if (!$user) {
                        continue;
                    }

                    $this->logMember([
                        'telegram_id' => (string) $user['id'],
                        'username' => $user['username'] ?? '',
                        'first_name' => $user['first_name'] ?? '',
                        'last_name' => $user['last_name'] ?? '',
                        'is_public' => isset($user['username']) ? 1 : 0,
                        'joined_from_channel' => '@' . $username,
                        'last_active_at' => $this->formatStatusDetail($user['status'] ?? []),
                        'online_status' => $this->formatStatusLabel($user['status'] ?? []),
                    ], $templateId);
                    $discovered++;
                }

                $offset += $limit;
                $this->sleepFor('rate_limit_discovery_delay_ms', 1500);
            } while (true);

            return ['discovered' => $discovered];
        } catch (Throwable $e) {
            $this->flagAccountError($accountId, $e);
            throw $e;
        }
    }

    public function searchChannels(int $accountId, string $query, ?int $templateId = null): array
    {
        $account = $this->getAccount($accountId);

        try {
            $client = $this->buildClient($account);
            $response = $client->contacts->search([
                'q' => $query,
                'limit' => 50,
            ]);

            $results = [];
            foreach ($response['chats'] ?? [] as $chat) {
                $type = $this->determineChannelType($chat);
                if ($type === null) {
                    continue;
                }

                $payload = [
                    'telegram_id' => (string) ($chat['id'] ?? ''),
                    'access_hash' => isset($chat['access_hash']) ? (string) $chat['access_hash'] : null,
                    'username' => $chat['username'] ?? null,
                    'title' => $chat['title'] ?? '',
                    'type' => $type,
                    'is_public' => isset($chat['username']) ? 1 : 0,
                    'extra' => json_encode([
                        'participants_count' => $chat['participants_count'] ?? null,
                        'verified' => $chat['verified'] ?? false,
                    ], JSON_UNESCAPED_UNICODE),
                ];

                if ($payload['telegram_id'] === '') {
                    continue;
                }

                $channelId = ChannelTarget::upsert($payload);
                if ($templateId) {
                    $this->attachChannelToTemplate($channelId, $templateId);
                }

                $stored = ChannelTarget::findWithTemplates($channelId);
                if ($stored) {
                    $results[] = $stored;
                }
            }

            return $results;
        } catch (Throwable $e) {
            $this->flagAccountError($accountId, $e);
            throw $e;
        }
    }

    public function runDueDispatchJobs(): void
    {
        $account = TelegramAccount::firstReady();
        if (!$account) {
            throw new RuntimeException('Gönderim için hazır durumdaki aktif bir hesap bulunamadı.');
        }

        $jobs = DispatchJob::dueJobs();
        foreach ($jobs as $job) {
            try {
                $this->runDispatchJob($job, (int) $account['id']);
                DispatchJob::update((int) $job['id'], [
                    'status' => 'completed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Throwable $e) {
                DispatchJob::update((int) $job['id'], [
                    'status' => 'failed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                throw $e;
            }
        }
    }

    public function logMember(array $data, ?int $templateId = null): void
    {
        $existing = $this->findMemberByTelegramId($data['telegram_id']);
        if ($existing) {
            Member::update((int) $existing['id'], [
                'username' => $data['username'] ?? $existing['username'],
                'first_name' => $data['first_name'] ?? $existing['first_name'],
                'last_name' => $data['last_name'] ?? $existing['last_name'],
                'is_public' => $data['is_public'] ?? $existing['is_public'],
                'joined_from_channel' => $data['joined_from_channel'] ?? $existing['joined_from_channel'],
                'last_active_at' => $data['last_active_at'] ?? $existing['last_active_at'],
                'online_status' => $data['online_status'] ?? $existing['online_status'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($templateId) {
                $this->attachMemberToTemplate((int) $existing['id'], $templateId);
            }
            return;
        }

        $memberId = Member::create([
            'telegram_id' => $data['telegram_id'],
            'username' => $data['username'] ?? '',
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'is_public' => $data['is_public'] ?? 0,
            'joined_from_channel' => $data['joined_from_channel'] ?? '',
            'last_active_at' => $data['last_active_at'] ?? '',
            'online_status' => $data['online_status'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($templateId) {
            $this->attachMemberToTemplate($memberId, $templateId);
        }
    }

    private function sendTemplateMessage(API $client, array $template, $peer, ?string $scheduledFor): void
    {
        $payload = [
            'peer' => $peer,
            'message' => $template['body'],
        ];

        if ($scheduledFor) {
            $timestamp = strtotime($scheduledFor);
            if ($timestamp !== false && $timestamp > time()) {
                $payload['schedule_date'] = $timestamp;
            }
        }

        $attachmentPath = $template['attachment_path'] ? storage_path('uploads/' . $template['attachment_path']) : null;
        if ($attachmentPath && file_exists($attachmentPath)) {
            $uploaded = $client->upload($attachmentPath, $template['attachment_name'] ?? basename($attachmentPath));
            $payload['media'] = [
                '_' => 'inputMediaUploadedDocument',
                'file' => $uploaded,
                'mime_type' => $template['attachment_type'] ?? mime_content_type($attachmentPath) ?: 'application/octet-stream',
                'attributes' => [
                    [
                        '_' => 'documentAttributeFilename',
                        'file_name' => $template['attachment_name'] ?? basename($attachmentPath),
                    ],
                ],
            ];
            $client->messages->sendMedia($payload);
            return;
        }

        $client->messages->sendMessage($payload);
    }

    private function buildClient(array $account, bool $start = true): API
    {
        $credentials = $this->getApiCredentials();
        $apiId = (int) ($credentials['api_id'] ?? 0);
        $apiHash = $credentials['api_hash'] ?? '';

        if ($apiId === 0 || $apiHash === '') {
            throw new RuntimeException('Telegram API kimlik bilgileri ayarlanmadı.');
        }

        $settings = new Settings();
        if (method_exists($settings, 'getIpc')) {
            $ipcSettings = $settings->getIpc();
            if ($ipcSettings) {
                if (method_exists($ipcSettings, 'setEnabled')) {
                    $ipcSettings->setEnabled(false);
                } elseif (method_exists($ipcSettings, 'setIpcDisabled')) {
                    $ipcSettings->setIpcDisabled(true);
                } elseif (method_exists($ipcSettings, 'setEnableIpc')) {
                    $ipcSettings->setEnableIpc(false);
                }
                if (method_exists($settings, 'setIpc')) {
                    $settings->setIpc($ipcSettings);
                }
            }
        }
        $appInfo = new AppInfo();
        $appInfo->setApiId($apiId);
        $appInfo->setApiHash($apiHash);
        $appInfo->setDeviceModel((string) Config::get('telegram.app.device_model', 'NoaSoft Automation Panel'));
        $appInfo->setSystemVersion((string) Config::get('telegram.app.system_version', 'AlmaLinux 8'));
        $appInfo->setLangCode((string) Config::get('telegram.app.lang_code', 'tr'));
        $settings->setAppInfo($appInfo);

        $loggerSettings = new LoggerSettings();
        $loggerSettings->setType(Logger::FILE_LOGGER);
        $loggerSettings->setLevel(Logger::LEVEL_WARNING);
        $loggerSettings->setExtra($this->getLogPath((int) $account['id']));
        $settings->setLogger($loggerSettings);

        $sessionPath = $this->getSessionPath((int) $account['id']);
        if (!is_dir(dirname($sessionPath))) {
            mkdir(dirname($sessionPath), 0775, true);
        }

        $client = new API($sessionPath, $settings);
        if ($start) {
            $client->start();
        }

        return $client;
    }

    private function getSessionPath(int $accountId): string
    {
        $dir = Config::get('telegram.session_dir', storage_path('sessions'));
        $dir = rtrim((string) $dir, '\\/');
        return $dir . '/account-' . $accountId . '.madeline';
    }

    private function getLogPath(int $accountId): string
    {
        $dir = Config::get('telegram.log_dir', storage_path('logs'));
        $dir = rtrim((string) $dir, '\\/');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir . '/telegram-account-' . $accountId . '.log';
    }

    private function sleepFor(string $settingKey, int $defaultMs): void
    {
        $value = Setting::get($settingKey, (string) $defaultMs);
        $delay = (int) ($value !== null ? $value : $defaultMs);
        if ($delay > 0) {
            usleep($delay * 1000);
        }
    }

    private function resolvePeer(API $client, string $targetType, string $targetValue, array $metadata)
    {
        $targetValue = trim($targetValue);

        if ($targetType === 'direct') {
            $member = $metadata['member'] ?? $metadata;
            if (!empty($member['username'])) {
                return $client->getPwrChat('@' . ltrim((string) $member['username'], '@'));
            }

            if ($targetValue !== '') {
                return $client->getPwrChat($targetValue);
            }

            if (!empty($member['telegram_id'])) {
                return $client->getPwrChat((string) $member['telegram_id']);
            }
        }

        if (in_array($targetType, ['channel', 'group'], true)) {
            return $this->resolveChannelPeer($client, $metadata, $targetValue);
        }

        if ($targetValue !== '') {
            return $client->getPwrChat($targetValue);
        }

        throw new RuntimeException('Hedef çözümlenemedi.');
    }

    private function resolveChannelPeer(API $client, array $metadata, string $targetValue)
    {
        $channel = $metadata['channel'] ?? $metadata;

        if (!empty($channel['telegram_id']) && !empty($channel['access_hash'])) {
            return [
                '_' => 'inputPeerChannel',
                'channel_id' => (int) $channel['telegram_id'],
                'access_hash' => (int) $channel['access_hash'],
            ];
        }

        if (!empty($channel['username'])) {
            return $client->getPwrChat('@' . ltrim((string) $channel['username'], '@'));
        }

        if ($targetValue !== '') {
            return $client->getPwrChat($targetValue);
        }

        if (!empty($channel['telegram_id'])) {
            return $client->getPwrChat((string) $channel['telegram_id']);
        }

        throw new RuntimeException('Kanal bilgisi çözümlenemedi.');
    }

    private function formatStatusLabel(array $status): string
    {
        return $status['_'] ?? 'bilinmiyor';
    }

    private function formatStatusDetail(array $status): string
    {
        $type = $status['_'] ?? '';
        return match ($type) {
            'userStatusOnline' => isset($status['expires']) ? 'Online (çıkış ' . date('c', (int) $status['expires']) . ')' : 'Online',
            'userStatusOffline' => isset($status['was_online']) ? 'Son görülme ' . date('Y-m-d H:i', (int) $status['was_online']) : 'Çevrimdışı',
            'userStatusRecently' => 'Son zamanlarda aktif',
            'userStatusLastWeek' => 'Son 1 hafta',
            'userStatusLastMonth' => 'Son 1 ay',
            default => $type,
        };
    }

    private function getAccount(int $accountId): array
    {
        $account = TelegramAccount::find($accountId);
        if (!$account) {
            throw new RuntimeException('Telegram hesabı bulunamadı.');
        }

        return $account;
    }

    private function flagAccountError(int $accountId, Throwable $e): void
    {
        TelegramAccount::update($accountId, [
            'last_error' => $e->getMessage(),
            'session_status' => 'error',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function attachMemberToTemplate(int $memberId, int $templateId): void
    {
        AudienceTemplateMember::attach($templateId, $memberId);
    }

    private function attachChannelToTemplate(int $channelId, int $templateId): void
    {
        AudienceTemplateChannel::attach($templateId, $channelId);
    }

    private function determineChannelType(array $chat): ?string
    {
        if (!empty($chat['megagroup'])) {
            return 'group';
        }

        if (!empty($chat['broadcast'])) {
            return 'channel';
        }

        if (($chat['_'] ?? '') === 'channel') {
            return !empty($chat['megagroup']) ? 'group' : 'channel';
        }

        return null;
    }

    private function findMemberByTelegramId(string $telegramId): ?array
    {
        $stmt = Member::connection()->prepare('SELECT * FROM members WHERE telegram_id = :telegram_id LIMIT 1');
        $stmt->execute(['telegram_id' => $telegramId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
