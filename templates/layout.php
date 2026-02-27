<?php
/** @var array<string,string> $settings */
$languages = available_languages();
?>
<?php
$renderMenuDesktop = function (array $items, bool $isSub = false) use (&$renderMenuDesktop): void {
  if (!$items) return;
  echo $isSub ? '<div class="menu-dropdown">' : '';
  foreach ($items as $item) {
    $hasChildren = !empty($item['children']);
    echo '<div class="menu-item'.($hasChildren ? ' has-dropdown' : '').'">';
    echo '<a href="'.htmlspecialchars((string)$item['href'], ENT_QUOTES).'">'.htmlspecialchars((string)$item['label'], ENT_QUOTES).'</a>';
    if ($hasChildren) {
      $renderMenuDesktop($item['children'], true);
    }
    echo '</div>';
  }
  echo $isSub ? '</div>' : '';
};

$renderMenuMobile = function (array $items) use (&$renderMenuMobile): void {
  foreach ($items as $item) {
    $hasChildren = !empty($item['children']);
    if ($hasChildren) {
      echo '<details class="mobile-dropdown"><summary>'.htmlspecialchars((string)$item['label'], ENT_QUOTES).'</summary>';
      echo '<a class="mobile-menu-link is-parent" href="'.htmlspecialchars((string)$item['href'], ENT_QUOTES).'">'.htmlspecialchars((string)$item['label'], ENT_QUOTES).'</a>';
      echo '<div class="mobile-submenu">';
      $renderMenuMobile($item['children']);
      echo '</div></details>';
    } else {
      echo '<a class="mobile-menu-link" href="'.htmlspecialchars((string)$item['href'], ENT_QUOTES).'">'.htmlspecialchars((string)$item['label'], ENT_QUOTES).'</a>';
    }
  }
};
?>

<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($settings['meta_title'] ?? 'CargoAfrik', ENT_QUOTES) ?></title>
  <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? 'Corporate cargo and logistics platform', ENT_QUOTES) ?>">
  <link rel="icon" href="<?= htmlspecialchars($settings['favicon_path'] ?? '/assets/favicon.ico', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
</head>
<body><div class="page-shell">
<header class="site-header">
  <div class="header-strip">
    <div class="container strip-inner">
      <p><?= t('front', 'top_message') ?></p>
      <div class="lang-switch dropdown">
        <button type="button" id="langToggle">
           <?= htmlspecialchars(strtoupper($lang), ENT_QUOTES) ?>
         </button>
        <div id="langMenu" class="lang-menu">
        <?php foreach ($languages as $code => $name): ?>
          <a class="<?= $code === $lang ? 'active' : '' ?>" href="?lang=<?= htmlspecialchars($code, ENT_QUOTES) ?>"><?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?> - <?= htmlspecialchars($name, ENT_QUOTES) ?></a>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="container nav-shell">
    <a class="logo" href="/"><img src="<?= htmlspecialchars($settings['logo_path'] ?? 'https://dummyimage.com/180x45/ffcc00/111&text=CargoAfrik', ENT_QUOTES) ?>" alt="logo"></a>
    <nav class="desktop-menu">
      <?php $renderMenuDesktop($menuTree); ?>
    </nav>
    <button id="mobileToggle" class="mobile-toggle">☰</button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <?php $renderMenuMobile($menuTree); ?>
  </div>
</header>
<main>
  <?php include __DIR__ . '/pages/' . $page . '.php'; ?>
</main>
<footer class="footer">
  <div class="container footer-grid footer-grid-pro">
    <div>
      <strong><?= htmlspecialchars($settings['company_name'] ?? 'CargoAfrik Logistics', ENT_QUOTES) ?></strong>
      <p><?= htmlspecialchars($settings['company_address'] ?? '-', ENT_QUOTES) ?></p>
      <p><?= t('front', 'email') ?>: <?= htmlspecialchars($settings['company_email'] ?? '-', ENT_QUOTES) ?></p>
      <p><?= t('front', 'phone') ?>: <?= htmlspecialchars($settings['company_phone'] ?? '-', ENT_QUOTES) ?></p>
    </div>
    <div>
      <h4><?= t('front','newsletter_title') ?></h4>
      <p class="subtext"><?= t('front','newsletter_desc') ?></p>
      <form id="newsletterForm" class="newsletter-form">
        <input type="email" name="email" placeholder="name@company.com" required>
        <button type="submit"><?= t('front','newsletter_btn') ?></button>
      </form>
    </div>
  </div>
</footer>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>window.CSRF_TOKEN = '<?= csrf_token() ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
