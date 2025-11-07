<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\Setting;
use App\Models\TelegramAccount;

final class TelegramService
{
    public function getApiCredentials(): array
    {
        return [
            'api_id' => Setting::get('telegram_api_id', ''),
            'api_hash' => Setting::get('telegram_api_hash', ''),
        ];
    }

    public function queueMemberDiscovery(int $accountId, string $channelUsername): void
    {
        // Placeholder for real Telegram API integration.
        // In production, implement asynchronous job that respects rate limits.
    }

    public function logMember(array $data): void
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
            return;
        }

        Member::create([
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
    }

    private function findMemberByTelegramId(string $telegramId): ?array
    {
        $stmt = Member::connection()->prepare('SELECT * FROM members WHERE telegram_id = :telegram_id LIMIT 1');
        $stmt->execute(['telegram_id' => $telegramId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
