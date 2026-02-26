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

try {
    if (($segments[0] ?? '') === 'tracking') {
        $page = 'track';
    } elseif (($segments[0] ?? '') === 'pricing') {
        $page = 'pricing';
    } elseif (($segments[0] ?? '') === 'contact') {
        $page = 'contact';
    } elseif (($segments[0] ?? '') === 'active-shipments') {
        $page = 'active-shipments';
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

    $stmt = db()->prepare('SELECT m.id, m.page_id, m.sort_order, pt.title, p.slug FROM menus m JOIN pages p ON p.id = m.page_id JOIN page_translations pt ON pt.page_id = p.id AND pt.lang_code = :lang WHERE p.is_active = 1 ORDER BY m.sort_order ASC');
    $stmt->execute(['lang' => $lang]);
    $menus = $stmt->fetchAll();

    if (!$menus) {
        $stmt->execute(['lang' => DEFAULT_LANG]);
        $menus = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    $menus = [];
}

include __DIR__ . '/templates/layout.php';
