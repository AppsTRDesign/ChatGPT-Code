<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;
use PDO;

abstract class Model
{
    protected static function db(): PDO
    {
        return Database::connection();
    }
}
