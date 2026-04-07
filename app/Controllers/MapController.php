<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\MapService;

final class MapController
{
    public function __construct(private readonly MapService $mapService = new MapService())
    {
    }

    public function countries(Request $request): void
    {
        Response::json(['data' => $this->mapService->countries()]);
    }

    public function regions(Request $request): void
    {
        Response::json(['data' => $this->mapService->regions()]);
    }
}
