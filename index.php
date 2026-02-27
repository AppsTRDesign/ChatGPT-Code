<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$settings = settings();
$lang = current_lang();

$uri = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$segments = $uri === '' ? [] : explode('/', $uri);

$page = 'home';
$payload = [];

/* ROUTING */
$routes = [
    'tracking' => 'track',
    'pricing' => 'pricing',
    'contact' => 'contact',
    'active-shipments' => 'active-shipments',
    'documents' => 'documents',
];

if (isset($routes[$segments[0] ?? ''])) {
    $page = $routes[$segments[0]];
}

/* MENÜLER */
$stmt = db()->prepare(<<<SQL
    SELECT
      m.id, m.item_type, m.page_id, m.system_key, m.title,
      m.url, m.parent_id, m.sort_order,
      p.slug,
      COALESCE(mt.title, m.title) AS label
    FROM menus m
    LEFT JOIN pages p ON p.id = m.page_id AND p.is_active = 1
    LEFT JOIN menu_translations mt ON mt.menu_id = m.id AND mt.lang_code = :lang
    WHERE m.is_active = 1
    ORDER BY
      m.parent_id IS NOT NULL,
      m.parent_id,
      m.sort_order
SQL
);
$stmt->execute(['lang' => $lang]);
$rows = $stmt->fetchAll();

/* HREF */
$systemMap = [
    'tracking' => '/tracking',
    'pricing' => '/pricing',
    'contact' => '/contact',
    'active-shipments' => '/active-shipments',
    'documents' => '/documents',
];

/* TREE */
$items = [];
foreach ($rows as $row) {
    $row['href'] =
        $row['item_type'] === 'system'
            ? ($systemMap[$row['system_key']] ?? '/')
            : ($row['url'] ?? '#');

    $row['children'] = [];
    $items[$row['id']] = $row;
}

$menuTree = [];
foreach ($items as $id => &$item) {
    if ($item['parent_id'] && isset($items[$item['parent_id']])) {
        $items[$item['parent_id']]['children'][] = &$item;
    } else {
        $menuTree[] = &$item;
    }
}
unset($item);

include __DIR__ . '/templates/layout.php';
