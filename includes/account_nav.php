<?php

function render_account_nav(string $active): void
{
    $items = [
        'profil' => ['label' => 'Profil', 'url' => '/profile'],
        'adresler' => ['label' => 'Adresler', 'url' => '/addresses'],
        'siparisler' => ['label' => 'Siparişler', 'url' => '/orders'],
        'favoriler' => ['label' => 'Favoriler', 'url' => '/favorites'],
        'yorumlar' => ['label' => 'Yorumlar', 'url' => '/reviews'],
    ];

    echo "<nav class=\"account-nav\">\n";
    foreach ($items as $key => $item) {
        $class = $key === $active ? ' class="active"' : '';
        echo "<a{$class} href=\"{$item['url']}\">{$item['label']}</a>\n";
    }
    echo "</nav>\n";
}
