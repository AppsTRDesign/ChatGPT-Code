<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Session;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        view($template, $data);
    }

    protected function json(array $data, int $code = 200): void
    {
        json($data, $code);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}
