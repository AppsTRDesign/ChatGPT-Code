<?php

declare(strict_types=1);

use App\Core\DB;

require __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = DB::connection();
} catch (Throwable $e) {
    echo "DB bağlantısı kurulamadı: {$e->getMessage()}\n";
    exit(1);
}

$files = glob(__DIR__ . '/db/migrations/*.sql') ?: [];
sort($files, SORT_NATURAL);

if (count($files) === 0) {
    echo "Migration dosyası bulunamadı.\n";
    exit(1);
}

$failed = false;

foreach ($files as $file) {
    $name = basename($file);
    echo "\n==> Çalıştırılıyor: {$name}\n";

    $sql = trim((string) file_get_contents($file));
    if ($sql === '') {
        echo "Boş dosya, atlandı.\n";
        continue;
    }

    try {
        $pdo->exec($sql);
        echo "OK\n";
    } catch (Throwable $e) {
        $failed = true;
        echo "HATA: {$e->getMessage()}\n";
        echo "Dosya: {$file}\n";
    }
}

if ($failed) {
    echo "\nKurulum tamamlanamadı. Hataları düzeltip tekrar çalıştırın.\n";
    exit(1);
}

echo "\nTüm migration dosyaları başarıyla çalıştırıldı.\n";
exit(0);
