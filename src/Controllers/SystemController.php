<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\GameService;

final class SystemController
{
    public function health(): void
    {
        $health = (new GameService())->healthSnapshot();
        $statusCode = ($health['ok'] ?? false) ? 200 : 503;
        Response::json($health, $statusCode);
    }
}
