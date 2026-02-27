<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$tab = $_GET['tab'] ?? 'dashboard';
$sub = $_GET['sub'] ?? '';
$cfg = settings();
$csrf = csrf_token();
$ymLang = current_lang() === 'tr' ? 'tr_TR' : 'en_US';
$ymKey = htmlspecialchars((string)($cfg['yandex_api_key'] ?? ''), ENT_QUOTES);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CargoAfrik Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script src="https://api-maps.yandex.ru/v3/?apikey=<?= $ymKey ?>&lang=<?= htmlspecialchars($ymLang, ENT_QUOTES) ?>"></script>
</head>
<body class="admin-body">
<div class="admin-layout">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="brand">CargoAfrik Admin</div>
    <a class="side-link <?= $tab==='dashboard'?'active':'' ?>" href="?tab=dashboard">Dashboard</a>
    <a class="side-link <?= $tab==='pages'?'active':'' ?>" href="?tab=pages">Sayfa Ekle / Düzenle</a>
    <a class="side-link <?= $tab==='menus'?'active':'' ?>" href="?tab=menus">Menü Yönetimi</a>
    <div class="side-group <?= $tab==='shipments'?'open':'' ?>">
      <button type="button" class="side-toggle">Kargo Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='shipments' && $sub==='create'?'active':'' ?>" href="?tab=shipments&sub=create">Kargo Ekle / Düzenle</a>
        <a class="side-link <?= $tab==='shipments' && $sub==='status'?'active':'' ?>" href="?tab=shipments&sub=status">Durum Güncelle</a>
      </div>
    </div>
    <div class="side-group <?= $tab==='pricing'?'open':'' ?>">
      <button type="button" class="side-toggle">Fiyatlama Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='pricing' && $sub==='country'?'active':'' ?>" href="?tab=pricing&sub=country">Ülke Ekle / Düzenle</a>
        <a class="side-link <?= $tab==='pricing' && $sub==='category'?'active':'' ?>" href="?tab=pricing&sub=category">Kategori Ekle / Düzenle</a>
        <a class="side-link <?= $tab==='pricing' && $sub==='price'?'active':'' ?>" href="?tab=pricing&sub=price">Fiyat Ekle</a>
      </div>
    </div>
    <a class="side-link <?= $tab==='settings'?'active':'' ?>" href="?tab=settings">Site Ayarları</a>
    <a class="side-link <?= $tab==='languages'?'active':'' ?>" href="?tab=languages">Dil Yönetimi</a>
    <a class="side-link <?= $tab==='admin'?'active':'' ?>" href="?tab=admin">Admin Ayarları</a>
    <a class="side-link danger" href="/admin/logout.php">Çıkış</a>
  </aside>

  <main class="admin-main">
    <button class="mobile-side-btn" id="mobileSideBtn">☰ Menü</button>

    <?php if ($tab === 'dashboard'): ?>
      <div class="admin-grid">
        <div class="admin-card"><h3>Toplam Kargo</h3><p><?= (int) db()->query('SELECT COUNT(*) FROM shipments')->fetchColumn() ?></p></div>
        <div class="admin-card"><h3>Toplam Sayfa</h3><p><?= (int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn() ?></p></div>
        <div class="admin-card admin-full"><h3>Aktif Kargolar Haritası</h3><p><a href="/active-shipments" target="_blank">Open Active Shipments Page</a></p></div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'pages'): ?>
      <div class="admin-grid">
        <div class="admin-card admin-full"><h3>Dinamik Sayfa Ekle / Güncelle</h3>
          <form id="pageForm">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="number" name="page_id" placeholder="Sayfa ID (güncelleme için)">
            <input type="text" name="slug_source" placeholder="Slug başlığı" required>
            <select class="lang-options" name="lang_code"></select>
            <input type="text" name="title" placeholder="Başlık" required>
            <textarea id="pageEditor" name="content_html" rows="12" placeholder="HTML içerik" required></textarea>
            <button>Kaydet</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'menus'): ?>
      <div class="admin-grid">
        <div class="admin-card"><h3>Dinamik Menü Yönetimi</h3><form id="menuForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="menuPageId" name="page_id"></select><input type="number" name="sort_order" value="1"><button>Ekle</button></form></div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'shipments'): ?>
      <div class="admin-grid">
        <div class="admin-card">
          <h3>Kargo Ekle / Düzenle</h3>
          <form id="shipmentForm">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <select id="shipmentTrackingSelect" name="existing_tracking"><option value="">Yeni kayıt</option></select>
            <input name="tracking_number" placeholder="Tracking no" required>
            <div class="group-title">Rota</div>
            <input name="origin_country" placeholder="Çıkış ülke" required>
            <input name="origin_city" placeholder="Çıkış şehir" required>
            <input name="destination_country" placeholder="Varış ülke" required>
            <input name="destination_city" placeholder="Varış şehir" required>
            <input name="current_status" placeholder="Durum" required>
            <textarea name="description" placeholder="Açıklama"></textarea>
            <div class="group-title">Gönderici Bilgileri</div>
            <input name="sender_name" placeholder="Ad Soyad">
            <input name="sender_company" placeholder="Şirket">
            <input name="sender_phone" placeholder="Telefon">
            <div class="group-title">Alıcı Bilgileri</div>
            <input name="receiver_name" placeholder="Ad Soyad">
            <input name="receiver_phone" placeholder="Telefon">
            <input name="receiver_address" placeholder="Adres">
            <input id="shipmentLat" name="current_latitude" placeholder="Lat">
            <input id="shipmentLng" name="current_longitude" placeholder="Lng">
            <button>Kaydet / Güncelle</button>
          </form>
        </div>
        <div class="admin-card"><h3>Yandex Konum Seçici</h3><div id="mapPicker"></div></div>

        <div class="admin-card admin-full">
          <h3>Durum Güncelle (Seçili Kargo)</h3>
          <form id="eventForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="shipmentTrackingSelect2" name="tracking_number"></select><input name="status_code" placeholder="Status code" required><input name="status_note" placeholder="Açıklama"><input name="country" placeholder="Ülke"><input name="city" placeholder="Şehir"><input name="latitude" placeholder="Lat"><input name="longitude" placeholder="Lng"><button>Durum Ekle</button></form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'pricing'): ?>
      <div class="admin-grid">
        <div class="admin-card"><h3>Ülke Ekle / Düzenle</h3><form id="countryForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="countrySelectAdmin" name="country_id"><option value="">Yeni ülke</option></select><input name="name" placeholder="Country name (EN)" required><input name="currency_code" placeholder="Currency (USD, EUR)" required><button>Kaydet</button></form></div>
        <div class="admin-card"><h3>Kategori Ekle / Düzenle</h3><form id="categoryForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="categorySelectAdmin" name="category_id"><option value="">Yeni kategori</option></select><input name="title" placeholder="Category title (EN)" required><textarea name="description" placeholder="Description"></textarea><button>Kaydet</button></form></div>

        <div class="admin-card"><h3>Ülke Çevirisi</h3><form id="countryTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="countrySelectAdmin2" name="country_id"></select><select class="lang-options" name="lang_code"></select><input name="name" placeholder="Çeviri ülke adı" required><button>Kaydet</button></form></div>
        <div class="admin-card"><h3>Kategori Çevirisi</h3><form id="categoryTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="categorySelectAdmin2" name="category_id"></select><select class="lang-options" name="lang_code"></select><input name="title" placeholder="Başlık" required><textarea name="description" placeholder="Açıklama"></textarea><button>Kaydet</button></form></div>

        <div class="admin-card admin-full"><h3>Fiyat Ekle (Ülke + Kategori)</h3><form id="priceConfigForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="priceCountryId" name="country_id"></select><select id="priceCategoryId" name="category_id"></select><input type="number" step="0.01" name="price_amount" placeholder="Ücret" required><button>Kaydet</button></form></div>
        <div class="admin-card admin-full"><h3>Mevcut Fiyatlar</h3><table class="list-table"><thead><tr><th>Ülke</th><th>Para Birimi</th><th>Kategori</th><th>Fiyat</th></tr></thead><tbody id="pricingTableBody"></tbody></table></div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'settings'): ?>
      <div class="admin-grid">
        <div class="admin-card admin-full"><h3>Site Ayarları</h3>
          <form id="settingsForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div class="group-title">Genel</div>
            <input name="site_name" value="<?= htmlspecialchars($cfg['site_name'] ?? '', ENT_QUOTES) ?>" placeholder="Site adı">
            <input name="meta_title" value="<?= htmlspecialchars($cfg['meta_title'] ?? '', ENT_QUOTES) ?>" placeholder="Meta title">
            <input name="meta_description" value="<?= htmlspecialchars($cfg['meta_description'] ?? '', ENT_QUOTES) ?>" placeholder="Meta description">
            <div class="group-title">Kurumsal</div>
            <input name="company_name" value="<?= htmlspecialchars($cfg['company_name'] ?? '', ENT_QUOTES) ?>" placeholder="Şirket adı">
            <input name="company_email" value="<?= htmlspecialchars($cfg['company_email'] ?? '', ENT_QUOTES) ?>" placeholder="E-posta">
            <input name="company_phone" value="<?= htmlspecialchars($cfg['company_phone'] ?? '', ENT_QUOTES) ?>" placeholder="Telefon">
            <input name="company_address" value="<?= htmlspecialchars($cfg['company_address'] ?? '', ENT_QUOTES) ?>" placeholder="Adres">
            <div class="group-title">Logo/Favicon</div>
            <input type="file" name="logo_file" accept="image/*"><input type="file" name="favicon_file" accept="image/*">
            <input name="logo_path" value="<?= htmlspecialchars($cfg['logo_path'] ?? '', ENT_QUOTES) ?>" placeholder="Logo URL">
            <input name="favicon_path" value="<?= htmlspecialchars($cfg['favicon_path'] ?? '', ENT_QUOTES) ?>" placeholder="Favicon URL">
            <div class="group-title">Yandex Konum</div>
            <input id="companyLat" name="company_latitude" value="<?= htmlspecialchars($cfg['company_latitude'] ?? '41.01', ENT_QUOTES) ?>" placeholder="Lat">
            <input id="companyLng" name="company_longitude" value="<?= htmlspecialchars($cfg['company_longitude'] ?? '28.97', ENT_QUOTES) ?>" placeholder="Lng">
            <input name="yandex_api_key" value="<?= htmlspecialchars($cfg['yandex_api_key'] ?? '', ENT_QUOTES) ?>" placeholder="Yandex API Key">
            <div id="settingsMap"></div>
            <button>Kaydet</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'languages'): ?>
      <div class="admin-grid">
        <div class="admin-card"><h3>Dil Ekle / Aktifleştir</h3><form id="languageForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="code" placeholder="es" required><input name="name" placeholder="Español" required><input name="sort_order" type="number" value="10"><button>Kaydet</button></form></div>
        <div class="admin-card"><h3>Tekil Çeviri</h3><form id="langForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select class="lang-options" name="lang_code"></select><input name="group_name" placeholder="front" required><input name="key_name" placeholder="hero_title" required><textarea name="text_value" placeholder="Metin"></textarea><button>Kaydet</button></form></div>
        <div class="admin-card admin-full"><h3>Dil Bazlı JSON Düzenleme</h3><form id="langJsonLoadForm"><select id="jsonLang" class="lang-options" name="lang"></select><button type="submit">Yükle</button></form><form id="translationJsonForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><textarea id="jsonEditor" name="json_payload" rows="14" placeholder='{"en":{"front":{"hero_title":"..."}}}'></textarea><button>JSON Kaydet</button></form><button id="exportTranslationsJson" type="button">Tüm Çevirileri Dışa Aktar</button></div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'admin'): ?>
      <div class="admin-grid"><div class="admin-card"><h3>Şifre Değiştir</h3><form id="adminPasswordForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="current_password" type="password" placeholder="Mevcut şifre" required><input name="new_password" type="password" placeholder="Yeni şifre" required><button>Güncelle</button></form></div></div>
    <?php endif; ?>
  </main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="/assets/admin/admin.js"></script>
</body>
</html>
