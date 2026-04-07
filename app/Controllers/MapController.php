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

    public function regionDetail(Request $request): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Response::json(['error' => 'invalid_request'], 422);
            return;
        }
        $detail = $this->mapService->regionDetail($id);
        if (!$detail) {
            Response::json(['error' => 'invalid_region'], 404);
            return;
        }
        Response::json(['data' => $detail]);
    }

    public function countryDetail(Request $request): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            Response::json(['error' => 'invalid_request'], 422);
            return;
        }
        $detail = $this->mapService->countryDetail($id);
        if (!$detail) {
            Response::json(['error' => 'invalid_region'], 404);
            return;
        }
        Response::json(['data' => $detail]);
    }
}
