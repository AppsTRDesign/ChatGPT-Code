<?php

namespace App\Services;

use DeviceDetector\DeviceDetector;

class DeviceService
{
    public static function parse(string $userAgent): array
    {
        $detector = new DeviceDetector($userAgent);
        $detector->parse();

        return [
            'client' => $detector->getClient(),
            'os' => $detector->getOs(),
            'device' => $detector->getDeviceName(),
            'brand' => $detector->getBrandName(),
            'model' => $detector->getModel()
        ];
    }
}
