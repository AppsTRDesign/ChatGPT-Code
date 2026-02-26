<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$tab = $_GET['tab'] ?? 'dashboard';
$cfg = settings();
$csrf = csrf_token();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CargoAfrik Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script src="https://api-maps.yandex.ru/2.1/?lang=tr_TR"></script>
</head>
<body class="admin-body">
<div class="admin-wrap">
  <div class="admin-top">
    <div class="admin-title">CargoAfrik Admin</div>
    <a href="/admin/logout.php">Çıkış</a>
  </div>

  <nav class="admin-nav">
    <?php $tabs=['dashboard'=>'Dashboard','pages'=>'Sayfa Yönetimi','menus'=>'Menü Yönetimi','shipments'=>'Kargo Yönetimi','pricing'=>'Fiyatlama Yönetimi','settings'=>'Site Ayarları','languages'=>'Dil Yönetimi','admin'=>'Admin Ayarları']; ?>
    <?php foreach ($tabs as $k => $v): ?><a class="<?= $tab === $k ? 'active' : '' ?>" href="?tab=<?= $k ?>"><?= $v ?></a><?php endforeach; ?>
  </nav>

  <?php if ($tab === 'dashboard'): ?>
    <div class="admin-grid">
      <div class="admin-card"><h3>Toplam Kargo</h3><p><?= (int) db()->query('SELECT COUNT(*) FROM shipments')->fetchColumn() ?></p></div>
      <div class="admin-card"><h3>Toplam Sayfa</h3><p><?= (int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn() ?></p></div>
      <div class="admin-card admin-full"><h3>Aktif Kargolar Haritası</h3><p><a href="/active-shipments" target="_blank">İngilizce aktif kargo haritasını aç</a></p></div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'pages'): ?>
    <div class="admin-grid">
      <div class="admin-card admin-full"><h3>Dinamik Sayfa Kaydet</h3>
        <form id="pageForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="number" name="page_id" placeholder="Sayfa ID (opsiyonel)"><input type="text" name="slug_source" placeholder="Slug başlığı" required><select class="lang-options" name="lang_code"></select><input type="text" name="title" placeholder="Başlık" required><textarea name="content_html" rows="8" placeholder="HTML içerik" required></textarea><button>Kaydet</button></form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'menus'): ?>
    <div class="admin-grid">
      <div class="admin-card"><h3>Menü Ekle</h3><form id="menuForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="menuPageId" name="page_id"></select><input type="number" name="sort_order" value="1"><button>Menüye Ekle</button></form></div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'shipments'): ?>
    <div class="admin-grid">
      <div class="admin-card">
        <h3>Kargo Ekle</h3>
        <form id="shipmentForm">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input name="tracking_number" placeholder="Tracking no" required>
          <input name="origin_country" placeholder="Çıkış ülke" required>
          <input name="origin_city" placeholder="Çıkış şehir" required>
          <input name="destination_country" placeholder="Varış ülke" required>
          <input name="destination_city" placeholder="Varış şehir" required>
          <input name="current_status" placeholder="Durum" required>
          <textarea name="description" placeholder="Açıklama"></textarea>
          <input name="sender_name" placeholder="Gönderici">
          <input name="sender_company" placeholder="Gönderici şirket">
          <input name="sender_phone" placeholder="Gönderici telefon">
          <input name="receiver_name" placeholder="Alıcı">
          <input name="receiver_phone" placeholder="Alıcı telefon">
          <input name="receiver_address" placeholder="Alıcı adres">
          <input id="shipmentLat" name="current_latitude" placeholder="Lat">
          <input id="shipmentLng" name="current_longitude" placeholder="Lng">
          <button>Kargo Kaydet</button>
        </form>
      </div>
      <div class="admin-card"><h3>Yandex Harita Konum Seçici</h3><div id="mapPicker"></div></div>
      <div class="admin-card admin-full">
        <h3>Durum / Güzergah Event Ekle</h3>
        <form id="eventForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="shipmentTrackingSelect" name="tracking_number"></select><input name="status_code" placeholder="Status code" required><input name="status_note" placeholder="Açıklama"><input name="country" placeholder="Ülke"><input name="city" placeholder="Şehir"><input name="latitude" placeholder="Lat"><input name="longitude" placeholder="Lng"><button>Durum Ekle</button></form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'pricing'): ?>
    <div class="admin-grid">
      <div class="admin-card"><h3>Ülke Ekle</h3><form id="countryForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="name" placeholder="Country name (EN)" required><button>Kaydet</button></form></div>
      <div class="admin-card"><h3>Kategori Ekle</h3><form id="categoryForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="title" placeholder="Category title (EN)" required><textarea name="description" placeholder="Description"></textarea><button>Kaydet</button></form></div>
      <div class="admin-card"><h3>Ülke Çevirisi</h3><form id="countryTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="countrySelectAdmin" name="country_id"></select><select class="lang-options" name="lang_code"></select><input name="name" placeholder="Çeviri ülke adı" required><button>Kaydet</button></form></div>
      <div class="admin-card"><h3>Kategori Çevirisi</h3><form id="categoryTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="categorySelectAdmin" name="category_id"></select><select class="lang-options" name="lang_code"></select><input name="title" placeholder="Başlık" required><textarea name="description" placeholder="Açıklama"></textarea><button>Kaydet</button></form></div>
      <div class="admin-card admin-full"><h3>Fiyat Ekle / Güncelle</h3><form id="priceConfigForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="priceCountryId" name="country_id"></select><select id="priceCategoryId" name="category_id"></select><input type="number" step="0.01" name="price_amount" placeholder="Ücret" required><button>Kaydet</button></form></div>
      <div class="admin-card admin-full"><h3>Fiyat Listesi</h3><table class="list-table"><thead><tr><th>Ülke</th><th>Kategori</th><th>Fiyat</th></tr></thead><tbody id="pricingTableBody"></tbody></table></div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'settings'): ?>
    <div class="admin-grid">
      <div class="admin-card admin-full"><h3>Site Ayarları (Gruplu)</h3>
        <form id="settingsForm" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <h4 class="admin-section-title">Genel</h4>
          <input name="site_name" value="<?= htmlspecialchars($cfg['site_name'] ?? '', ENT_QUOTES) ?>" placeholder="Site adı">
          <input name="meta_title" value="<?= htmlspecialchars($cfg['meta_title'] ?? '', ENT_QUOTES) ?>" placeholder="Meta title">
          <input name="meta_description" value="<?= htmlspecialchars($cfg['meta_description'] ?? '', ENT_QUOTES) ?>" placeholder="Meta description">

          <h4 class="admin-section-title">Kurumsal Bilgiler</h4>
          <input name="company_name" value="<?= htmlspecialchars($cfg['company_name'] ?? '', ENT_QUOTES) ?>" placeholder="Şirket adı">
          <input name="company_email" value="<?= htmlspecialchars($cfg['company_email'] ?? '', ENT_QUOTES) ?>" placeholder="E-posta">
          <input name="company_phone" value="<?= htmlspecialchars($cfg['company_phone'] ?? '', ENT_QUOTES) ?>" placeholder="Telefon">
          <input name="company_address" value="<?= htmlspecialchars($cfg['company_address'] ?? '', ENT_QUOTES) ?>" placeholder="Adres">

          <h4 class="admin-section-title">Branding</h4>
          <input type="file" name="logo_file" accept="image/*">
          <input type="file" name="favicon_file" accept="image/*">
          <input name="logo_path" value="<?= htmlspecialchars($cfg['logo_path'] ?? '', ENT_QUOTES) ?>" placeholder="Logo URL (opsiyonel)">
          <input name="favicon_path" value="<?= htmlspecialchars($cfg['favicon_path'] ?? '', ENT_QUOTES) ?>" placeholder="Favicon URL (opsiyonel)">

          <h4 class="admin-section-title">Yandex Map Konumu</h4>
          <input id="companyLat" name="company_latitude" value="<?= htmlspecialchars($cfg['company_latitude'] ?? '41.01', ENT_QUOTES) ?>" placeholder="Company Lat">
          <input id="companyLng" name="company_longitude" value="<?= htmlspecialchars($cfg['company_longitude'] ?? '28.97', ENT_QUOTES) ?>" placeholder="Company Lng">
          <input name="yandex_api_key" value="<?= htmlspecialchars($cfg['yandex_api_key'] ?? 'd0b1a4c0-60eb-4a39-b34a-61c68fffc2d6', ENT_QUOTES) ?>" placeholder="Yandex API Key">
          <div id="settingsMap"></div>
          <button>Ayarları Kaydet</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'languages'): ?>
    <div class="admin-grid">
      <div class="admin-card"><h3>Dil Ekle</h3><form id="languageForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="code" placeholder="es" required><input name="name" placeholder="Español" required><input name="sort_order" type="number" value="10"><button>Kaydet</button></form></div>
      <div class="admin-card"><h3>Tekil Çeviri Ekle</h3><form id="langForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select class="lang-options" name="lang_code"></select><input name="group_name" placeholder="front"><input name="key_name" placeholder="hero_title"><textarea name="text_value" placeholder="Metin"></textarea><button>Kaydet</button></form></div>
      <div class="admin-card admin-full"><h3>JSON Çeviri Import/Export</h3><form id="translationJsonForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><textarea name="json_payload" rows="10" placeholder='{"en":{"front":{"hello":"Hello"}}}'></textarea><button>JSON Import</button></form><button id="exportTranslationsJson" type="button">JSON Export</button></div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'admin'): ?>
    <div class="admin-grid">
      <div class="admin-card"><h3>Admin Şifre Değiştir</h3><form id="adminPasswordForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="current_password" type="password" placeholder="Mevcut şifre" required><input name="new_password" type="password" placeholder="Yeni şifre" required><button>Şifreyi Güncelle</button></form></div>
    </div>
  <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="/assets/admin/admin.js"></script>
</body>
</html>
