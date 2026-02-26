<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!admin_auth()) {
    json_response(false, 'Yetkisiz');
}

$out = [
    'countries' => db()->query('SELECT id, name FROM countries ORDER BY name')->fetchAll(),
    'categories' => db()->query('SELECT id, title FROM price_categories ORDER BY title')->fetchAll(),
    'pages' => db()->query("SELECT p.id, COALESCE(pt.title, p.slug) title FROM pages p LEFT JOIN page_translations pt ON pt.page_id = p.id AND pt.lang_code = 'en' ORDER BY p.id DESC")->fetchAll(),
    'languages' => db()->query('SELECT code, name FROM languages WHERE is_active = 1 ORDER BY sort_order, code')->fetchAll(),
    'shipments' => db()->query('SELECT tracking_number FROM shipments ORDER BY id DESC LIMIT 300')->fetchAll(),
];

json_response(true, 'ok', $out);
