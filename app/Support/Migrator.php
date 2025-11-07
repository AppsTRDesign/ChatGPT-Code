<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\Database;

final class Migrator
{
    public static function run(): void
    {
        $flag = storage_path('data/.migrated');
        if (file_exists($flag)) {
            return;
        }

        $connection = Database::connection();
        $sql = file_get_contents(database_path('migrations.sql'));
        if ($sql !== false) {
            $connection->exec($sql);
        }

        \App\Models\AdminUser::ensureDefaultAdmin();
        if (!is_dir(dirname($flag))) {
            mkdir(dirname($flag), 0775, true);
        }
        file_put_contents($flag, 'migrated: ' . date('c'));
    }
}
