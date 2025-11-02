<?php
use Helpers\Language;

/** @var array $context */
$context = $context ?? [];
extract($context, EXTR_SKIP);
$baseUrl ??= rtrim(BASE_URL, '/');
$asset ??= static fn(string $path): string => $baseUrl . '/' . ltrim($path, '/');
$templateStylesheet ??= $asset('assets/css/templates/' . ($selectedTemplate ?? 'menu1') . '.css');

$jsStrings = [
    'messages.error_generic' => Language::get('messages.error_generic', 'The operation could not be completed.'),
    'messages.currency_fetch' => Language::get('messages.currency_fetch', 'Currency conversion failed'),
    'messages.order_status_failed' => Language::get('messages.order_status_failed', 'Unable to load order status'),
    'menu.categories_all' => Language::get('menu.categories_all', 'All'),
    'menu.daily_empty' => Language::get('menu.daily_empty', 'Daily menu is being prepared.'),
    'menu.products_empty' => Language::get('menu.products_empty', 'No products match your filters.'),
    'menu.category_empty' => Language::get('menu.category_empty', 'No products were found in this category.'),
    'menu.cart.summary' => Language::get('menu.cart.summary', ':count items'),
    'menu.cart.empty' => Language::get('menu.cart.empty', 'Your cart is empty.'),
    'menu.cart.remove' => Language::get('menu.cart.remove', 'Remove'),
    'menu.toast_added' => Language::get('menu.toast_added', ':item has been added to your cart.'),
    'menu.variant_default' => Language::get('menu.variant_default', 'Standard'),
    'menu.order_success' => Language::get('menu.order_success', 'Your order has been received.'),
    'menu.orders_empty' => Language::get('menu.orders_empty', 'You do not have any active orders.'),
    'contact.sending' => Language::get('contact.sending', 'Sending your message...'),
    'contact.success' => Language::get('contact.success', 'Your message has been sent successfully.'),
    'menu.table_missing' => Language::get('menu.table_missing', 'Table information could not be found.'),
    'menu.waiter_called' => Language::get('menu.waiter_called', 'Waiter call sent.'),
    'menu.waiter_notice' => Language::get('menu.waiter_notice', 'Your waiter request has been sent.'),
    'menu.waiter_status' => Language::get('menu.waiter_status', 'Waiter status: :status'),
    'menu.order_status_update' => Language::get('menu.order_status_update', 'Order :status'),
    'menu.add_to_cart' => Language::get('menu.add_to_cart', 'Add to Cart'),
    'menu.table_prefix' => Language::get('menu.table_prefix', 'Table'),
];

$cartSummaryTemplate = Language::get('menu.cart.summary', ':count items');
$initialCartSummary = strtr($cartSummaryTemplate, [':count' => 0]);
$currentCurrencyCode = $currentCurrencyCode ?? $currentCurrency ?? $defaultCurrency ?? 'TRY';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($restaurant['name'] ?? 'QR Menu') ?></title>
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
                <p class="mb-1"><?= htmlspecialchars(Language::get('menu.greeting', 'Good day')) ?></p>
                <h2 class="mb-0"><?= htmlspecialchars($restaurant['name'] ?? Language::get('menu.guest', 'Guest')) ?></h2>
                <?php if ($tableName): ?>
                    <small class="d-block text-white-50"><?= htmlspecialchars(Language::get('menu.table_label', 'Table')) ?>: <?= htmlspecialchars($tableName) ?></small>
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
                <?= htmlspecialchars(Language::get('menu.call_waiter', 'Call Waiter')) ?>
            </button>
        </div>
    </div>
    <div class="search">
        <input type="search" id="searchMenu" placeholder="<?= htmlspecialchars(Language::get('menu.search', 'Search the menu')) ?>" />
        <div class="menu-filters">
            <label for="sortProducts" class="menu-filters__label"><?= htmlspecialchars(Language::get('menu.sort.label', 'Sort')) ?></label>
            <select id="sortProducts" class="form-select">
                <option value="name_asc"><?= htmlspecialchars(Language::get('menu.sort.name_asc', 'Name (A-Z)')) ?></option>
                <option value="name_desc"><?= htmlspecialchars(Language::get('menu.sort.name_desc', 'Name (Z-A)')) ?></option>
                <option value="price_asc"><?= htmlspecialchars(Language::get('menu.sort.price_asc', 'Price (Low to High)')) ?></option>
                <option value="price_desc"><?= htmlspecialchars(Language::get('menu.sort.price_desc', 'Price (High to Low)')) ?></option>
            </select>
        </div>
    </div>
</header>

<main class="menu-container">
    <section class="menu-page is-active" id="homePage">
        <div class="menu-block" id="dailySection">
            <div class="menu-block__header">
                <div>
                    <h2><?= htmlspecialchars(Language::get('menu.sections.daily_title', "Today's Menu")) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars(Language::get('menu.sections.daily_subtitle', 'Chef recommendations and special flavours')) ?></p>
                </div>
            </div>
            <div class="daily-slider" id="dailySlider"></div>
        </div>
        <div class="menu-block" id="categorySection">
            <div class="menu-block__header">
                <div>
                    <h2><?= htmlspecialchars(Language::get('menu.sections.categories_title', 'Categories')) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars(Language::get('menu.sections.categories_subtitle', 'Discover flavours by category')) ?></p>
                </div>
            </div>
            <div class="category-list" id="menuCategories"></div>
        </div>
        <div class="menu-block" id="productSection">
            <div class="menu-block__header">
                <div>
                    <h2><?= htmlspecialchars(Language::get('menu.sections.products_title', 'Products')) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars(Language::get('menu.sections.products_subtitle', 'Browse every dish on the menu')) ?></p>
                </div>
            </div>
            <div class="product-grid" id="menuProducts"></div>
        </div>
    </section>
    <section class="menu-page" id="ordersPage">
        <div class="order-status menu-block">
            <div class="order-status__header">
                <h2><?= htmlspecialchars(Language::get('menu.orders_title', 'Order Tracking')) ?></h2>
                <button type="button" id="refreshOrders" class="btn btn-light btn-sm"><?= htmlspecialchars(Language::get('menu.orders_refresh', 'Refresh')) ?></button>
            </div>
            <div id="orderStatusList" class="order-status__list"></div>
        </div>
    </section>

    <section class="menu-page" id="categoriesPage">
        <div class="menu-block" id="categoryPage">
            <div class="menu-block__header">
                <div>
                    <h2><?= htmlspecialchars(Language::get('menu.sections.categories_title', 'Categories')) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars(Language::get('menu.sections.category_prompt', 'Select categories to explore products')) ?></p>
                </div>
            </div>
            <div class="category-list category-list--grid" id="categoryPageList"></div>
        </div>
        <div class="menu-block" id="categoryProductsBlock">
            <div class="menu-block__header">
                <div>
                    <h2><?= htmlspecialchars(Language::get('menu.sections.products_title', 'Products')) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars(Language::get('menu.sections.category_products_subtitle', 'Products in the selected category')) ?></p>
                </div>
            </div>
            <div class="product-grid" id="categoryPageProducts"></div>
        </div>
    </section>

    <section class="menu-page" id="contactPage">
        <div class="menu-block contact-block" id="contactSection">
            <div class="menu-block__header">
                <div>
                    <h2><?= htmlspecialchars(Language::get('contact.title', 'Contact')) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars(Language::get('contact.subtitle', 'Reach out with your questions and requests')) ?></p>
                </div>
            </div>
            <div class="row g-4 contact-content">
                <div class="col-12 col-md-6 col-lg-5 d-flex order-1 order-md-1">
                    <div class="contact-details w-100">
                        <div class="contact-details__item">
                            <i class="bx bx-store"></i>
                            <div>
                                <strong><?= htmlspecialchars($restaurant['name'] ?? Language::get('menu.guest', 'Guest')) ?></strong>
                                <?php if (!empty($restaurant['description'])): ?>
                                    <p class="mb-0 text-muted"><?= htmlspecialchars($restaurant['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($restaurant['phone'])): ?>
                            <div class="contact-details__item">
                                <i class="bx bx-phone"></i>
                                <div>
                                    <strong><?= htmlspecialchars(Language::get('contact.phone', 'Phone')) ?></strong>
                                    <a href="tel:<?= htmlspecialchars($restaurant['phone']) ?>"><?= htmlspecialchars($restaurant['phone']) ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($restaurant['address'])): ?>
                            <div class="contact-details__item">
                                <i class="bx bx-map"></i>
                                <div>
                                    <strong><?= htmlspecialchars(Language::get('contact.address', 'Address')) ?></strong>
                                    <p class="mb-0"><?= htmlspecialchars($restaurant['address']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($contactEmail !== ''): ?>
                            <div class="contact-details__item">
                                <i class="bx bx-envelope"></i>
                                <div>
                                    <strong><?= htmlspecialchars(Language::get('contact.email', 'Email')) ?></strong>
                                    <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-7 d-flex order-2 order-md-2">
                    <form id="contactForm" class="contact-form w-100">
                        <h3 class="contact-form__title"><?= htmlspecialchars(Language::get('contact.form_title', 'Send a Message')) ?></h3>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(Language::get('contact.name_label', 'Full Name')) ?></label>
                            <input type="text" name="name" class="form-control" placeholder="<?= htmlspecialchars(Language::get('contact.name_placeholder', 'Your name')) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(Language::get('contact.email_label', 'Email')) ?></label>
                            <input type="email" name="email" class="form-control" placeholder="<?= htmlspecialchars(Language::get('contact.email_placeholder', 'name@example.com')) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(Language::get('contact.message_label', 'Message')) ?></label>
                            <textarea name="message" class="form-control" rows="4" placeholder="<?= htmlspecialchars(Language::get('contact.message_placeholder', 'Write your message here')) ?>" required></textarea>
                        </div>
                        <div id="contactFeedback" class="contact-feedback" role="status"></div>
                        <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(Language::get('contact.submit', 'Send')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<nav class="menu-bottom-nav" id="menuBottomNav">
    <button type="button" class="menu-bottom-nav__item active" data-target="homePage">
        <i class="bx bx-home-alt-2"></i>
        <span><?= htmlspecialchars(Language::get('menu.bottom.home', 'Home')) ?></span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="dailySection">
        <i class="bx bx-star"></i>
        <span><?= htmlspecialchars(Language::get('menu.bottom.daily', 'Daily')) ?></span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="ordersPage">
        <i class="bx bx-receipt"></i>
        <span><?= htmlspecialchars(Language::get('menu.bottom.orders', 'Orders')) ?></span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="categoriesPage">
        <i class="bx bx-category-alt"></i>
        <span><?= htmlspecialchars(Language::get('menu.bottom.categories', 'Categories')) ?></span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-target="contactPage">
        <i class="bx bx-phone-call"></i>
        <span><?= htmlspecialchars(Language::get('menu.bottom.contact', 'Contact')) ?></span>
    </button>
    <button type="button" class="menu-bottom-nav__item" data-action="cart">
        <i class="bx bx-cart"></i>
        <span><?= htmlspecialchars(Language::get('menu.bottom.cart', 'Cart')) ?></span>
    </button>
</nav>

<button class="cart-floating" id="cartButton">
    <span><?= htmlspecialchars(Language::get('app.orders', 'Orders')) ?></span>
    <div id="cartSummary"><span><?= htmlspecialchars($initialCartSummary) ?></span><strong><?= htmlspecialchars($currentCurrencyCode) ?> 0.00</strong></div>
</button>

<div class="cart-backdrop d-none" id="cartBackdrop"></div>
<div class="cart-drawer d-none" id="cartDrawer">
    <div class="cart-drawer__header">
        <h3><?= htmlspecialchars(Language::get('menu.cart.title', 'Your Cart')) ?></h3>
        <button type="button" class="btn-close" id="closeCart"></button>
    </div>
    <div id="cartItems" class="cart-drawer__items"></div>
    <div class="cart-drawer__footer">
        <div>
            <span><?= htmlspecialchars(Language::get('menu.cart.total', 'Total')) ?></span>
            <strong id="cartTotal"><?= htmlspecialchars($currentCurrencyCode) ?> 0.00</strong>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="clearCart"><?= htmlspecialchars(Language::get('menu.cart.clear', 'Clear')) ?></button>
            <button type="button" class="btn btn-success" id="submitOrder"><?= htmlspecialchars(Language::get('menu.cart.checkout', 'Confirm Order')) ?></button>
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
            <button type="button" class="btn btn-success" id="addToCartButton"><?= htmlspecialchars(Language::get('menu.add_to_cart', 'Add to Cart')) ?></button>
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
        addToCartText: <?= json_encode(Language::get('menu.add_to_cart', 'Add to Cart'), JSON_UNESCAPED_UNICODE) ?>,
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
        template: <?= json_encode($selectedTemplate, JSON_UNESCAPED_UNICODE) ?>,
        strings: <?= json_encode($jsStrings, JSON_UNESCAPED_UNICODE) ?>
    };
    document.documentElement.style.setProperty('--theme-color', <?= json_encode($restaurant['theme_color'] ?? '#0f9d58', JSON_UNESCAPED_UNICODE) ?>);
</script>
<script src="<?= htmlspecialchars($asset('assets/js/menu.js')) ?>"></script>
</body>
</html>
