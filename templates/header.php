<?php
$module = $_GET['module'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siyah&Beyaz Muhasebe</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <h1>Siyah &amp; Beyaz Muhasebe</h1>
    <div class="header-meta">
        <nav>
            <?php
            $links = [
                'dashboard' => 'Kontrol Paneli',
                'caris' => 'Cari Hesaplar',
                'stock' => 'Stok Yönetimi',
                'invoices' => 'Fatura &amp; Fiş',
                'payments' => 'Ödeme &amp; Tahsilat',
                'reports' => 'Raporlama',
                'banks' => 'Finansal Yönetim',
                'settings' => 'Ayarlar',
                'logs' => 'Kullanıcı Logları'
            ];
            foreach ($links as $slug => $label):
                $active = $module === $slug ? 'active' : '';
                echo "<a class=\"$active\" href=\"index.php?module=$slug\">$label</a>";
            endforeach;
            ?>
        </nav>
        <div class="user-meta">
            <span><?php echo htmlspecialchars($_SESSION['user'] ?? ''); ?></span>
            <a class="logout" href="admin/logout/">Çıkış</a>
        </div>
    </div>
</header>
<main>
