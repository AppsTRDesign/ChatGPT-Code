<?php
use Helpers\Language;

/** @var array $context */
$context = $context ?? [];
extract($context, EXTR_SKIP);
$baseUrl ??= rtrim(BASE_URL, '/');
$asset ??= static fn(string $path): string => $baseUrl . '/' . ltrim($path, '/');
$templateStylesheet ??= $asset('assets/css/templates/' . ($selectedTemplate ?? 'menu1') . '.css');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($restaurant['name'] ?? 'QR Menü') ?></title>
    <?php if (!empty($branding['favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($branding['favicon']) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-borderless/borderless.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-M9d1RChESyqCCpt5TR1+t0NenE2no0RvrRZtGJPD7W82dManIeZDV4SSQdlqzTeWY5Avzk3l3pNGdisM8z7jkQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($templateStylesheet) ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
</head>
<body class="menu-page template-<?= htmlspecialchars($selectedTemplate) ?>" data-base-url="<?= htmlspecialchars($baseUrl) ?>" data-base-currency="<?= htmlspecialchars($defaultCurrency) ?>" data-current-currency="<?= htmlspecialchars($currentCurrency) ?>" data-current-language="<?= htmlspecialchars($defaultLanguage) ?>" data-table-id="<?= htmlspecialchars((string)$tableId) ?>">
<header class="menu-hero" style="--theme-color: <?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>;">
    <div class="greeting">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($branding['logo'])): ?>
                <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name'] ?? '') ?>" style="height:64px;border-radius:16px;background:#ffffff;padding:8px;">
            <?php endif; ?>
            <div>
                <p class="mb-1">Günaydın</p>
                <h2 class="mb-0"><?= htmlspecialchars($restaurant['name'] ?? 'Misafir') ?></h2>
                <?php if ($tableName): ?>
                    <small class="d-block text-white-50">Masa: <?= htmlspecialchars($tableName) ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="menu-controls">
            <select id="languageSelect" class="form-select">
                <?php foreach ($languages as $language): ?>
                    <option value="<?= htmlspecialchars($language['code']) ?>" <?= $language['code'] === $defaultLanguage ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper($language['code'])) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="currencySelect" class="form-select">
                <?php foreach ($currencies as $currency): ?>
                    <?php
                        $code = strtoupper($currency['code'] ?? '');
                        $name = trim($currency['name'] ?? $code);
                        $symbol = trim($currency['symbol'] ?? '');
                    ?>
                    <option
                        value="<?= htmlspecialchars($code) ?>"
                        data-symbol="<?= htmlspecialchars($symbol) ?>"
                        data-name="<?= htmlspecialchars($name) ?>"
                        <?= $code === $currentCurrency ? 'selected' : '' ?>
                    ><?= htmlspecialchars($code) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="callWaiter" class="menu-controls__waiter">
                <?= htmlspecialchars(Language::get('menu.call_waiter')) ?>
            </button>
        </div>
    </div>
    <div class="search">
        <input type="search" id="searchMenu" placeholder="<?= htmlspecialchars(Language::get('menu.search', 'Menüde ara')) ?>" />
    </div>
</header>

<main class="menu-container">
    <section class="menu-section" id="homeSection">
        <div class="menu-block" id="dailySection">
            <div class="menu-block__header">
                <div>
                    <h2>Günün Menüsü</h2>
                    <p class="text-muted">Şefin önerileri ve özel tatlar</p>
                </div>
            </div>
            <div class="daily-slider" id="dailySlider"></div>
        </div>
        <div class="menu-block" id="categorySection">
            <div class="menu-block__header">
                <div>
                    <h2>Kategoriler</h2>
                    <p class="text-muted">Lezzetleri kategorilere göre keşfedin</p>
                </div>
            </div>
            <div class="category-list" id="menuCategories"></div>
        </div>
        <div class="menu-block" id="productSection">
            <div class="menu-block__header">
                <div>
                    <h2>Ürünler</h2>
                    <p class="text-muted">Menüdeki tüm ürünleri inceleyin</p>
                </div>
            </div>
            <div class="product-grid" id="menuProducts"></div>
        </div>
    </section>
    <section class="order-status menu-block" id="orderSection">
        <div class="order-status__header">
            <h2><?= htmlspecialchars(Language::get('menu.orders_title', 'Sipariş Takibi')) ?></h2>
            <button type="button" id="refreshOrders" class="btn btn-light btn-sm">Yenile</button>
        </div>
        <div id="orderStatusList" class="order-status__list"></div>
    </section>

    <section class="menu-block contact-block" id="contactSection">
        <div class="menu-block__header">
            <div>
                <h2>İletişim</h2>
                <p class="text-muted">Öneri, talep ve sorularınız için bize ulaşın</p>
            </div>
        </div>
        <div class="row g-4 contact-content">
            <div class="col-12 col-md-6 col-lg-5 d-flex order-1 order-md-1">
                <div class="contact-details w-100">
                    <div class="contact-details__item">
                        <i class="bx bx-store"></i>
                        <div>
                            <strong><?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?></strong>
                            <?php if (!empty($restaurant['description'])): ?>
                                <p class="mb-0 text-muted"><?= htmlspecialchars($restaurant['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($restaurant['phone'])): ?>
                        <div class="contact-details__item">
                            <i class="bx bx-phone"></i>
                            <div>
                                <strong>Telefon</strong>
                                <a href="tel:<?= htmlspecialchars($restaurant['phone']) ?>"><?= htmlspecialchars($restaurant['phone']) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($restaurant['address'])): ?>
                        <div class="contact-details__item">
                            <i class="bx bx-map"></i>
                            <div>
                                <strong>Adres</strong>
                                <p class="mb-0"><?= htmlspecialchars($restaurant['address']) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($contactEmail !== ''): ?>
                        <div class="contact-details__item">
                            <i class="bx bx-envelope"></i>
                            <div>
                                <strong>E-posta</strong>
                                <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-7 d-flex order-2 order-md-2">
                <form id="contactForm" class="contact-form w-100">
                    <h3 class="contact-form__title">Mesaj Gönder</h3>
                    <div class="mb-3">
                        <label class="form-label">Adınız Soyadınız</label>
                        <input type="text" name="name" class="form-control" placeholder="Adınız" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" placeholder="ornek@mail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mesajınız</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Mesajınızı buraya yazın" required></textarea>
                    </div>
                    <div id="contactFeedback" class="contact-feedback" role="status"></div>
                    <button type="submit" class="btn btn-primary w-100">Gönder</button>
                </form>
            </div>
        </div>
    </section>
</main>

<nav class="menu-bottom-nav" id="menuBottomNav">
    <button type="button" class="menu-bottom-nav__item active" data-target="homeSection">
        <i class="bx bx-home-alt-2"></i>
        <span>Ana Sayfa</span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="dailySection">
        <i class="bx bx-star"></i>
        <span>Günün</span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="orderSection">
        <i class="bx bx-receipt"></i>
        <span>Sipariş</span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="categorySection">
        <i class="bx bx-category-alt"></i>
        <span>Kategori</span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="contactSection">
        <i class="bx bx-phone-call"></i>
        <span>İletişim</span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-action="cart">
        <i class="bx bx-cart"></i>
        <span>Sepet</span>
    </button>
</nav>

<button class="cart-floating" id="cartButton">
    <span><?= htmlspecialchars(Language::get('app.orders')) ?></span>
    <div id="cartSummary"><span>0 ürün</span><strong><?= htmlspecialchars($currentCurrencyCode) ?> 0.00</strong></div>
</button>

<div class="cart-backdrop d-none" id="cartBackdrop"></div>
<div class="cart-drawer d-none" id="cartDrawer">
    <div class="cart-drawer__header">
        <h3>Sepetiniz</h3>
        <button type="button" class="btn-close" id="closeCart"></button>
    </div>
    <div id="cartItems" class="cart-drawer__items"></div>
    <div class="cart-drawer__footer">
        <div>
            <span>Toplam</span>
            <strong id="cartTotal"><?= htmlspecialchars($currentCurrencyCode) ?> 0.00</strong>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="clearCart">Temizle</button>
            <button type="button" class="btn btn-success" id="submitOrder">Siparişi Onayla</button>
        </div>
    </div>
</div>

<div class="product-overlay d-none" id="productOverlay">
    <div class="product-sheet">
        <div class="product-sheet__header">
            <h3 id="overlayProductName"></h3>
            <button type="button" class="btn-close" id="closeProductOverlay"></button>
        </div>
        <p id="overlayProductDescription" class="text-muted"></p>
        <div id="overlayVariants" class="overlay-variants"></div>
        <div class="product-sheet__footer">
            <div class="quantity-picker">
                <button type="button" id="qtyDecrease">-</button>
                <span id="qtyValue">1</span>
                <button type="button" id="qtyIncrease">+</button>
            </div>
            <button type="button" class="btn btn-success" id="addToCartButton">Sepete Ekle</button>
        </div>
    </div>
</div>

<audio id="audioOrder" preload="auto" src="<?= htmlspecialchars($orderSound) ?>"></audio>
<audio id="audioNotify" preload="auto" src="<?= htmlspecialchars($waiterSound) ?>"></audio>

<script>
    window.MENU_STATE = {
        currency: <?= json_encode($currentCurrency, JSON_UNESCAPED_UNICODE) ?>,
        languages: <?= json_encode(array_column($languages, 'code'), JSON_UNESCAPED_UNICODE) ?>,
        currencies: <?= json_encode($currencyCodes, JSON_UNESCAPED_UNICODE) ?>,
        currencyMeta: <?= json_encode($currencyMeta, JSON_UNESCAPED_UNICODE) ?>,
        addToCartText: <?= json_encode(Language::get('menu.add_to_cart', 'Sepete Ekle'), JSON_UNESCAPED_UNICODE) ?>,
        tableId: <?= json_encode($tableId, JSON_UNESCAPED_UNICODE) ?>,
        tableName: <?= json_encode($tableName, JSON_UNESCAPED_UNICODE) ?>,
        orderSound: <?= json_encode($orderSound, JSON_UNESCAPED_UNICODE) ?>,
        waiterSound: <?= json_encode($waiterSound, JSON_UNESCAPED_UNICODE) ?>,
        basePath: <?= json_encode($friendlyPath, JSON_UNESCAPED_UNICODE) ?>,
        baseUrl: <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE) ?>,
        contact: <?= json_encode([
            'name' => $restaurant['name'] ?? '',
            'phone' => $restaurant['phone'] ?? '',
            'address' => $restaurant['address'] ?? '',
            'email' => $contactEmail,
        ], JSON_UNESCAPED_UNICODE) ?>,
        template: <?= json_encode($selectedTemplate, JSON_UNESCAPED_UNICODE) ?>
    };
    document.documentElement.style.setProperty('--theme-color', <?= json_encode($restaurant['theme_color'] ?? '#0f9d58', JSON_UNESCAPED_UNICODE) ?>);
</script>
<script src="<?= htmlspecialchars($asset('assets/js/menu.js')) ?>"></script>
</body>
</html>
