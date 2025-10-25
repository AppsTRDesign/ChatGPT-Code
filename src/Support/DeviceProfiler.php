<?php

declare(strict_types=1);

namespace App\Support;

use DeviceDetector\DeviceDetector;

class DeviceProfiler
{
    private DeviceDetector $detector;

    public function __construct(string $userAgent)
    {
        $this->detector = new DeviceDetector($userAgent);
        $this->detector->skipBotDetection();
        $this->detector->parse();
    }

    public function toArray(): array
    {
        $clientInfo = $this->detector->getClient();
        $osInfo = $this->detector->getOs();
        $deviceName = $this->detector->getDeviceName();
        $brand = $this->detector->getBrandName();
        $model = $this->detector->getModel();

        return [
            'is_bot' => $this->detector->isBot(),
            'browser' => $clientInfo['name'] ?? 'Bilinmiyor',
            'browser_version' => $clientInfo['version'] ?? null,
            'platform' => $osInfo['name'] ?? 'Bilinmiyor',
            'platform_version' => $osInfo['version'] ?? null,
            'device' => $deviceName ?: 'Bilinmiyor',
            'brand' => $brand ?: 'Bilinmiyor',
            'model' => $model ?: null,
        ];
    }
}
