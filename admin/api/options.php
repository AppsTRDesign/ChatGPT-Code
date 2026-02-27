<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$out = [
    'countries' => db()->query('SELECT id, name, currency_code FROM countries ORDER BY name')->fetchAll(),
    'categories' => db()->query('SELECT id, title, description FROM price_categories ORDER BY title')->fetchAll(),
    'pages' => db()->query("SELECT p.id, COALESCE(pt.title, p.slug) title FROM pages p LEFT JOIN page_translations pt ON pt.page_id = p.id AND pt.lang_code = 'en' ORDER BY p.id DESC")->fetchAll(),
    'languages' => db()->query('SELECT code, name FROM languages WHERE is_active = 1 ORDER BY sort_order, code')->fetchAll(),
    'shipments' => db()->query('SELECT tracking_number FROM shipments ORDER BY id DESC LIMIT 300')->fetchAll(),
    'menus' => db()->query("SELECT m.id,m.parent_id,m.sort_order,m.item_type,m.system_key,m.title,m.url,COALESCE(pt.title,p.slug) page_title FROM menus m LEFT JOIN pages p ON p.id=m.page_id LEFT JOIN page_translations pt ON pt.page_id=p.id AND pt.lang_code='en' ORDER BY COALESCE(m.parent_id,0),m.sort_order,m.id")->fetchAll(),
    'system_links' => [
        ['key'=>'tracking','label'=>'Tracking','url'=>'/tracking'],
        ['key'=>'pricing','label'=>'Pricing','url'=>'/pricing'],
        ['key'=>'contact','label'=>'Contact','url'=>'/contact'],
        ['key'=>'active-shipments','label'=>'Active Shipments','url'=>'/active-shipments'],
    ],
];

json_response(true, 'ok', $out);
