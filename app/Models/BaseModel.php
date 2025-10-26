<?php

namespace App\Models;

use App\Services\Container;
use PDO;

abstract class BaseModel
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Container::pdo();
    }
}
