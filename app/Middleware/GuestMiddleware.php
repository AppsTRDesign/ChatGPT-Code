<?php
declare(strict_types=1);

namespace App\Middleware;

final class GuestMiddleware
{
    public function handle(): bool
    {
        if (isset($_SESSION['auth'])) {
            redirect('/admin');
            return false;
        }

        return true;
    }
}
