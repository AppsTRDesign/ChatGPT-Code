<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Services\GameService;

final class DashboardController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(): void
    {
        if (!Auth::adminCheck()) {
            Response::redirect('/admin/login');
        }

        $game = new GameService();

        View::render('admin/dashboard', [
            'config' => $this->config,
            'csrf' => Csrf::token(),
            'world' => $game->adminWorldData(),
        ]);
    }

    public function addCountry(): void
    {
        if (!Auth::adminCheck() || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin?toast=Yetkisiz');
        }

        $res = (new GameService())->createCountry((string) ($_POST['code'] ?? ''), (string) ($_POST['name'] ?? ''), (string) ($_POST['flag'] ?? '🏳️'));
        Response::redirect('/admin?toast=' . urlencode($res['message']));
    }

    public function addCity(): void
    {
        if (!Auth::adminCheck() || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin?toast=Yetkisiz');
        }

        $res = (new GameService())->createCity((int) ($_POST['country_id'] ?? 0), (string) ($_POST['name'] ?? ''), (float) ($_POST['lat'] ?? 0), (float) ($_POST['lng'] ?? 0));
        Response::redirect('/admin?toast=' . urlencode($res['message']));
    }

    public function addResourceDistribution(): void
    {
        if (!Auth::adminCheck() || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin?toast=Yetkisiz');
        }

        $res = (new GameService())->addCountryResource((int) ($_POST['country_id'] ?? 0), (int) ($_POST['resource_id'] ?? 0), (int) ($_POST['daily_yield'] ?? 0));
        Response::redirect('/admin?toast=' . urlencode($res['message']));
    }

    public function addMapLayer(): void
    {
        if (!Auth::adminCheck() || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin?toast=Yetkisiz');
        }

        $res = (new GameService())->addMapLayer(
            (int) ($_POST['country_id'] ?? 0),
            (string) ($_POST['layer_key'] ?? ''),
            (string) ($_POST['color_hex'] ?? '#0D6EFD'),
            (float) ($_POST['intensity'] ?? 1),
            (string) ($_POST['note'] ?? '')
        );
        Response::redirect('/admin?toast=' . urlencode($res['message']));
    }

    public function addCityPoi(): void
    {
        if (!Auth::adminCheck() || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin?toast=Yetkisiz');
        }

        $res = (new GameService())->addCityPoi(
            (int) ($_POST['city_id'] ?? 0),
            (string) ($_POST['poi_type'] ?? ''),
            (string) ($_POST['title'] ?? ''),
            (string) ($_POST['description'] ?? '')
        );
        Response::redirect('/admin?toast=' . urlencode($res['message']));
    }
}
