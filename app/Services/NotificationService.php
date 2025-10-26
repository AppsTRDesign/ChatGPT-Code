<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Subscriber;

class NotificationService
{
    private Subscriber $subscribers;
    private NotificationLog $logs;

    public function __construct(?Subscriber $subscribers = null, ?NotificationLog $logs = null)
    {
        $this->subscribers = $subscribers ?? new Subscriber();
        $this->logs = $logs ?? new NotificationLog();
    }

    public function dispatchCampaign(int $campaignId, array $campaignData): int
    {
        $targets = $this->resolveTargets($campaignData['target_segment'] ?? null, $campaignData['target_tags'] ?? null);
        $sent = 0;

        foreach ($targets as $subscriber) {
            $status = 'success';
            $message = 'Simulated push delivery';

            $this->logs->create([
                'campaign_id' => $campaignId,
                'subscriber_id' => $subscriber['id'],
                'status' => $status,
                'response_message' => $message,
            ]);

            $sent++;
        }

        return $sent;
    }

    private function resolveTargets(?string $segmentId, ?string $tags): array
    {
        $subs = $this->subscribers->all();
        if (!$segmentId && !$tags) {
            return $subs;
        }

        $tagList = array_filter(array_map('trim', explode(',', (string) $tags)));
        $filtered = [];
        foreach ($subs as $subscriber) {
            $subscriberTags = array_filter(array_map('trim', explode(',', (string) $subscriber['tags'])));

            if ($segmentId && (string) ($subscriber['segment_id'] ?? '') !== (string) $segmentId) {
                continue;
            }

            if ($tagList && !array_intersect($tagList, $subscriberTags)) {
                continue;
            }

            $filtered[] = $subscriber;
        }

        return $filtered;
    }
}
