<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$settings = settings();
$lang = current_lang();
$uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
$segments = $uri === '' ? [] : explode('/', $uri);

$page = 'home';
$payload = [];
$menus = [];
$menuTree = [];

try {
    if (($segments[0] ?? '') === 'tracking') {
        $page = 'track';
    } elseif (($segments[0] ?? '') === 'pricing') {
        $page = 'pricing';
    } elseif (($segments[0] ?? '') === 'contact') {
        $page = 'contact';
    } elseif (($segments[0] ?? '') === 'active-shipments') {
        $page = 'active-shipments';
    } elseif (($segments[0] ?? '') === 'documents') {
        $page = 'documents';
    } elseif (($segments[0] ?? '') === 'page' && isset($segments[1], $segments[2])) {
        $stmt = db()->prepare('SELECT p.id, pt.title, pt.content_html FROM pages p JOIN page_translations pt ON pt.page_id = p.id AND pt.lang_code = :lang WHERE p.id = :id AND p.slug = :slug AND p.is_active = 1');
        $stmt->execute(['lang' => $lang, 'id' => (int) $segments[1], 'slug' => $segments[2]]);
        $pageRow = $stmt->fetch();

        if (!$pageRow) {
            $stmt->execute(['lang' => DEFAULT_LANG, 'id' => (int) $segments[1], 'slug' => $segments[2]]);
            $pageRow = $stmt->fetch();
        }

        if ($pageRow) {
            $page = 'dynamic';
            $payload['page'] = $pageRow;
        }
    }

    $stmt = db()->prepare("SELECT m.id,m.item_type,m.page_id,m.system_key,m.title,m.url,m.parent_id,m.sort_order,
    p.slug,
    COALESCE(pt.title, ptt.title) AS page_title,
    mt.title AS menu_title,
    mtd.title AS menu_title_default
    FROM menus m
    LEFT JOIN pages p ON p.id = m.page_id AND p.is_active = 1
    LEFT JOIN page_translations pt ON pt.page_id = p.id AND pt.lang_code = :lang
    LEFT JOIN page_translations ptt ON ptt.page_id = p.id AND ptt.lang_code = :default_lang
    LEFT JOIN menu_translations mt ON mt.menu_id = m.id AND mt.lang_code = :lang
    LEFT JOIN menu_translations mtd ON mtd.menu_id = m.id AND mtd.lang_code = :default_lang
    WHERE m.is_active = 1
    ORDER BY COALESCE(m.parent_id,0), m.sort_order, m.id");
    $stmt->execute(['lang' => $lang, 'default_lang' => DEFAULT_LANG]);
    $menus = $stmt->fetchAll();

    foreach ($menus as &$menu) {
        $menu['label'] = (string)($menu['menu_title'] ?: $menu['menu_title_default'] ?: $menu['title'] ?: $menu['page_title'] ?: ucfirst((string)$menu['system_key']));
        if (($menu['item_type'] ?? '') === 'system') {
            $map = [
                'tracking' => '/tracking',
                'pricing' => '/pricing',
                'contact' => '/contact',
                'active-shipments' => '/active-shipments',
                'documents' => '/documents',
            ];
            $menu['href'] = $map[$menu['system_key']] ?? '/';
        } elseif (($menu['item_type'] ?? '') === 'custom') {
            $menu['href'] = (string)($menu['url'] ?? '#');
        } else {
            $menu['href'] = !empty($menu['page_id']) ? '/page/' . (int)$menu['page_id'] . '/' . (string)$menu['slug'] : '#';
        }
    }
    unset($menu);

    $grouped = [];
    foreach ($menus as $item) {
        $parent = (int)($item['parent_id'] ?? 0);
        $grouped[$parent][] = $item;
    }
    foreach ($grouped as &$grp) {
        usort($grp, static function (array $a, array $b): int {
            return ((int)$a['sort_order'] <=> (int)$b['sort_order']) ?: ((int)$a['id'] <=> (int)$b['id']);
        });
    }
    unset($grp);

    $buildTree = function (int $parentId) use (&$buildTree, $grouped): array {
        $list = $grouped[$parentId] ?? [];
        foreach ($list as &$item) {
            $item['children'] = $buildTree((int)$item['id']);
        }
        unset($item);
        return $list;
    };
    $menuTree = $buildTree(0);
    if (!$menuTree) {
        $menuTree = [
            ['label' => t('front','track', $lang), 'href' => '/tracking', 'children' => []],
            ['label' => t('front','pricing', $lang), 'href' => '/pricing', 'children' => []],
            ['label' => t('front','contact', $lang), 'href' => '/contact', 'children' => []],
            ['label' => t('front','active_shipments', $lang), 'href' => '/active-shipments', 'children' => []],
            ['label' => t('front','documents', $lang), 'href' => '/documents', 'children' => []],
        ];
    }
} catch (Throwable $e) {
    $menus = [];
    $menuTree = [
        ['label' => t('front','track', $lang), 'href' => '/tracking', 'children' => []],
        ['label' => t('front','pricing', $lang), 'href' => '/pricing', 'children' => []],
        ['label' => t('front','contact', $lang), 'href' => '/contact', 'children' => []],
        ['label' => t('front','active_shipments', $lang), 'href' => '/active-shipments', 'children' => []],
        ['label' => t('front','documents', $lang), 'href' => '/documents', 'children' => []],
    ];
}

include __DIR__ . '/templates/layout.php';
