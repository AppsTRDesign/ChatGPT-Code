<?php
/** @var array<string,string> $settings */
$languages = available_languages();
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
</head>
<body>
<header class="site-header">
  <div class="header-strip">
    <div class="container strip-inner">
      <p><?= t('front', 'top_message') ?></p>
      <div class="lang-switch">
        <?php foreach ($languages as $code => $name): ?>
          <a class="<?= $code === $lang ? 'active' : '' ?>" href="?lang=<?= htmlspecialchars($code, ENT_QUOTES) ?>"><?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="container nav-shell">
    <a class="logo" href="/"><img src="<?= htmlspecialchars($settings['logo_path'] ?? 'https://dummyimage.com/180x45/ffcc00/111&text=CargoAfrik', ENT_QUOTES) ?>" alt="logo"></a>
    <nav class="desktop-menu">
      <a href="/kargo-takip"><?= t('front', 'track') ?></a>
      <a href="/fiyat-hesapla"><?= t('front', 'pricing') ?></a>
      <a href="/iletisim"><?= t('front', 'contact') ?></a>
      <?php foreach ($menus as $item): ?>
        <a href="/sayfa/<?= (int) $item['page_id'] ?>/<?= htmlspecialchars($item['slug'], ENT_QUOTES) ?>"><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></a>
      <?php endforeach; ?>
    </nav>
    <button id="mobileToggle" class="mobile-toggle">☰</button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="/kargo-takip"><?= t('front', 'track') ?></a>
    <a href="/fiyat-hesapla"><?= t('front', 'pricing') ?></a>
    <a href="/iletisim"><?= t('front', 'contact') ?></a>
    <?php foreach ($menus as $item): ?>
      <a href="/sayfa/<?= (int) $item['page_id'] ?>/<?= htmlspecialchars($item['slug'], ENT_QUOTES) ?>"><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></a>
    <?php endforeach; ?>
  </div>
</header>
<main>
  <?php include __DIR__ . '/pages/' . $page . '.php'; ?>
</main>
<footer class="footer">
  <div class="container footer-grid">
    <div>
      <strong><?= htmlspecialchars($settings['company_name'] ?? 'CargoAfrik Logistics', ENT_QUOTES) ?></strong>
      <p><?= htmlspecialchars($settings['company_address'] ?? '-', ENT_QUOTES) ?></p>
    </div>
    <div>
      <p><?= t('front', 'email') ?>: <?= htmlspecialchars($settings['company_email'] ?? '-', ENT_QUOTES) ?></p>
      <p><?= t('front', 'phone') ?>: <?= htmlspecialchars($settings['company_phone'] ?? '-', ENT_QUOTES) ?></p>
    </div>
  </div>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>window.CSRF_TOKEN = '<?= csrf_token() ?>';</script>
<script src="/assets/js/app.js"></script>
</body>
</html>
