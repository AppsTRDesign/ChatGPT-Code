<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapModel;

final class MapService
{
    public function __construct(private readonly MapModel $mapModel = new MapModel())
    {
    }

    public function countries(): array
    {
        return $this->mapModel->countries();
    }

    public function regions(): array
    {
        return $this->mapModel->regions();
    }

    public function regionDetail(int $regionId): ?array
    {
        return $this->mapModel->regionDetail($regionId);
    }

    public function countryDetail(int $countryRegionId): ?array
    {
        return $this->mapModel->countryDetailFromRegion($countryRegionId);
    }
}
