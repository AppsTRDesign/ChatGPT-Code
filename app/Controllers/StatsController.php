<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\MapModel;

final class StatsController
{
    public function __construct(private readonly MapModel $mapModel = new MapModel())
    {
    }

    public function overview(Request $request): void
    {
        Response::json(['data' => $this->mapModel->dashboardStats()]);
    }
}
