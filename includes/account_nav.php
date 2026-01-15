<?php

function render_account_nav(string $active): void
{
    $items = [
        'profil' => ['label' => 'Profil', 'url' => '/profil'],
        'adresler' => ['label' => 'Adresler', 'url' => '/adresler'],
        'siparisler' => ['label' => 'Siparişler', 'url' => '/siparisler'],
        'favoriler' => ['label' => 'Favoriler', 'url' => '/favoriler'],
        'yorumlar' => ['label' => 'Yorumlar', 'url' => '/yorumlar'],
    ];

    echo "<nav class=\"account-nav\">\n";
    foreach ($items as $key => $item) {
        $class = $key === $active ? ' class="active"' : '';
        echo "<a{$class} href=\"{$item['url']}\">{$item['label']}</a>\n";
    }
    echo "</nav>\n";
}
