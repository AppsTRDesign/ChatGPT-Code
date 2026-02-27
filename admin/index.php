<?php

declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$tab = $_GET['tab'] ?? 'dashboard';
$sub = $_GET['sub'] ?? '';
$cfg = settings();
$csrf = csrf_token();
$ymLang = yandex_locale();
$ymKey = htmlspecialchars((string)($cfg['yandex_api_key'] ?? ''), ENT_QUOTES);
?>
<!doctype html>
<html lang="<?= htmlspecialchars(yandex_locale(), ENT_QUOTES) ?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CargoAfrik Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script src="https://api-maps.yandex.ru/v3/?apikey=<?= $ymKey ?>&lang=<?= htmlspecialchars($ymLang, ENT_QUOTES) ?>"></script>
</head>
<body class="admin-body">
<div class="admin-layout">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="brand-row"><div class="brand">CargoAfrik Admin</div><button type="button" id="mobileSideClose" class="mobile-side-close">✕</button></div>
    <a class="side-link <?= $tab==='dashboard'?'active':'' ?>" href="?tab=dashboard">Dashboard</a>
    <div class="side-group <?= $tab==='pages'?'open':'' ?>">
      <button type="button" class="side-toggle">Sayfa Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='pages' && ($sub===''||$sub==='list')?'active':'' ?>" href="?tab=pages&sub=list">Eklenen Sayfalar</a>
        <a class="side-link <?= $tab==='pages' && $sub==='new'?'active':'' ?>" href="?tab=pages&sub=new">Yeni Sayfa Ekle</a>
      </div>
    </div>
    <a class="side-link <?= $tab==='menus'?'active':'' ?>" href="?tab=menus">Menü Yönetimi</a>
    <div class="side-group <?= $tab==='documents'?'open':'' ?>">
      <button type="button" class="side-toggle">Belgelerimiz</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='documents' && ($sub===''||$sub==='list')?'active':'' ?>" href="?tab=documents&sub=list">Eklenen Belgeler</a>
        <a class="side-link <?= $tab==='documents' && $sub==='new'?'active':'' ?>" href="?tab=documents&sub=new">Belge Ekle</a>
      </div>
    </div>
    <div class="side-group <?= $tab==='shipments'?'open':'' ?>">
      <button type="button" class="side-toggle">Kargo Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='shipments' && ($sub===''||$sub==='list')?'active':'' ?>" href="?tab=shipments&sub=list">Eklenen Kargolar</a>
        <a class="side-link <?= $tab==='shipments' && $sub==='new'?'active':'' ?>" href="?tab=shipments&sub=new">Yeni Kargo Ekle</a>
        <a class="side-link <?= $tab==='shipments' && $sub==='status'?'active':'' ?>" href="?tab=shipments&sub=status">Durum Güncelle</a>
      </div>
    </div>
    <div class="side-group <?= $tab==='countries'?'open':'' ?>">
      <button type="button" class="side-toggle">Ülke Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='countries' && ($sub===''||$sub==='list')?'active':'' ?>" href="?tab=countries&sub=list">Eklenen Ülkeler</a>
        <a class="side-link <?= $tab==='countries' && $sub==='new'?'active':'' ?>" href="?tab=countries&sub=new">Yeni Ülke Ekle</a>
      </div>
    </div>
    <div class="side-group <?= $tab==='pricing'?'open':'' ?>">
      <button type="button" class="side-toggle">Fiyatlama Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='pricing' && ($sub===''||$sub==='category-list')?'active':'' ?>" href="?tab=pricing&sub=category-list">Eklenen Kategoriler</a>
        <a class="side-link <?= $tab==='pricing' && $sub==='category-new'?'active':'' ?>" href="?tab=pricing&sub=category-new">Kategori Ekle</a>
        <a class="side-link <?= $tab==='pricing' && $sub==='transport'?'active':'' ?>" href="?tab=pricing&sub=transport">Taşıma Seçenekleri</a>
        <a class="side-link <?= $tab==='pricing' && $sub==='weights'?'active':'' ?>" href="?tab=pricing&sub=weights">Kilo Fiyatları</a>
        <a class="side-link <?= $tab==='pricing' && $sub==='price'?'active':'' ?>" href="?tab=pricing&sub=price">Fiyat Ekle</a>
      </div>
    </div>
    <a class="side-link <?= $tab==='settings'?'active':'' ?>" href="?tab=settings">Site Ayarları</a>
    <div class="side-group <?= $tab==='languages'?'open':'' ?>">
      <button type="button" class="side-toggle">Dil Yönetimi</button>
      <div class="side-sub">
        <a class="side-link <?= $tab==='languages' && ($sub===''||$sub==='list')?'active':'' ?>" href="?tab=languages&sub=list">Eklenen Diller</a>
        <a class="side-link <?= $tab==='languages' && $sub==='new'?'active':'' ?>" href="?tab=languages&sub=new">Yeni Dil Ekle</a>
              </div>
    </div>
    <a class="side-link <?= $tab==='admin'?'active':'' ?>" href="?tab=admin">Admin Ayarları</a>
    <a class="side-link danger" href="/admin/logout.php">Çıkış</a>
  </aside>

  <main class="admin-main">
    <button class="mobile-side-btn" id="mobileSideBtn">☰ Menü</button>

    <?php if ($tab === 'dashboard'): ?>
      <div class="admin-grid">
        <div class="admin-card"><h3>Toplam Kargo</h3><p><?= (int) db()->query('SELECT COUNT(*) FROM shipments')->fetchColumn() ?></p></div>
        <div class="admin-card"><h3>Toplam Sayfa</h3><p><?= (int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn() ?></p></div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'pages'): $pageSub = $sub ?: 'list'; ?>
      <div class="admin-grid">
        <?php if ($pageSub === 'list'): ?>
          <div class="admin-card admin-full"><h3>Eklenmiş Sayfalar</h3><div class="table-wrap"><table class="list-table"><thead><tr><th>ID</th><th>Başlık</th><th>Slug</th><th>Aksiyon</th></tr></thead><tbody id="pageTableBody"></tbody></table></div></div>
        <?php else: ?>
          <div class="admin-card admin-full"><h3><?= $pageSub === 'edit' ? 'Sayfa Düzenle' : 'Sayfa Ekle' ?></h3>
            <form id="pageForm">
              <input type="hidden" name="csrf" value="<?= $csrf ?>">
              <input type="hidden" name="page_id" id="pageId">
              <input type="hidden" name="translations_json" id="pageTranslationsJson">
              <p class="subtext">Tek sayfa kaydında tüm dillerin başlık/içerik alanlarını yönetin. SEO slug otomatik olarak İngilizce başlıktan üretilir.</p>
              <div id="pageTranslationsWrap" class="form-split-2"></div>
              <button>Kaydet</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'menus'): ?>
      <div class="admin-grid">
        <div class="admin-card admin-full"><h3>Profesyonel Menü Yönetimi</h3>
          <form id="menuForm" class="menu-form-grid">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="id" id="menuItemId">
            <label>Menü Tipi</label>
            <select id="menuItemType" name="item_type">
              <option value="page">Dinamik Sayfa</option>
              <option value="system">Sistem Linki</option>
              <option value="custom">Özel Link</option>
            </select>
            <div id="menuPageField">
              <label>Dinamik Sayfa</label>
              <select id="menuPageId" name="page_id"></select>
            </div>
            <div id="menuSystemField">
              <label>Sistem Linki</label>
              <select id="menuSystemKey" name="system_key"></select>
            </div>
            <div id="menuTitleField">
              <label>Menü Başlığı</label>
              <input type="text" name="title" placeholder="Menü başlığı (opsiyonel)">
            </div>
            <div id="menuUrlField">
              <label>Özel URL</label>
              <input type="text" name="url" placeholder="https://... veya /kurumsal-link">
            </div>
            <label>Üst Menü</label><select id="menuParentId" name="parent_id"><option value="">Üst Menü Yok (Ana Menü)</option></select>
            <label>Durum</label><select name="is_active"><option value="1">Aktif</option><option value="0">Pasif</option></select>
            <div class="menu-form-actions"><button type="submit">Kaydet</button><button type="button" id="menuFormReset" class="btn-muted">Temizle</button></div>
          </form>
        </div>
        <div class="admin-card">
          <h3>Menü Ağacı (Drag & Drop)</h3>
          <p class="subtext">Menü öğelerini sürükleyerek sırala ve alt menü oluştur.</p>
          <div id="menuTree" class="menu-tree"></div>
          <button id="saveMenuOrder" type="button">Menü Ağacını Kaydet</button>
        </div>
        <div class="admin-card"><h3>Menü Dil Çevirisi</h3><form id="menuTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="menuTranslationId" name="menu_id"></select><select class="lang-options" name="lang_code"></select><label>Çeviri Başlığı</label><input name="title" placeholder="Menü başlığı çevirisi" required><button>Kaydet</button></form></div>
        <div class="admin-card">
          <h3>Eklenmiş Menü Öğeleri</h3>
          <div class="table-wrap"><table class="list-table"><thead><tr><th>ID</th><th>Başlık</th><th>Tip</th><th>Üst Menü</th><th>Aksiyon</th></tr></thead><tbody id="menuTableBody"></tbody></table></div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'shipments'): $shipSub = $sub ?: 'list'; ?>
      <div class="admin-grid">
        <?php if ($shipSub === 'list'): ?>
          <div class="admin-card admin-full"><h3>Eklenmiş Kargolar</h3><div class="table-wrap"><table class="list-table"><thead><tr><th>ID</th><th>Tracking</th><th>Rota</th><th>Durum</th><th>Aksiyon</th></tr></thead><tbody id="shipmentTableBody"></tbody></table></div></div>
        <?php elseif ($shipSub === 'status'): ?>
          <div class="admin-card admin-full"><h3>Durum Güncelle</h3><form id="eventForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><label>Kargo</label><select id="shipmentTrackingSelect2" name="tracking_number"></select><label>Durum</label><select name="status_code" id="statusCodeSelect" required></select><label>Açıklama</label><input name="status_note" placeholder="Durum açıklaması"><label>Konum Ülkesi</label><select id="eventCountryId" name="country_id"></select><label>Konum Şehri</label><input name="city" placeholder="Şehir"><label>Anlık Enlem (lat)</label><input id="eventLat" name="latitude" placeholder="Örn: 41.008"><label>Anlık Boylam (lng)</label><input id="eventLng" name="longitude" placeholder="Örn: 28.978"><label class="form-label">Konum adresi ara</label><div class="search-row"><input id="eventMapSearchInput" placeholder="Konum adresi ara"><button type="button" id="eventMapSearchBtn">Ara</button></div><div id="eventMap"></div><button>Durum Ekle</button></form></div>
        <?php else: ?>
          <div class="admin-card admin-full"><h3>Yeni Kargo Ekle</h3>
            <form id="shipmentForm">
              <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <label>Tracking</label><input name="tracking_number" placeholder="Tracking no" required>
              <label>Durum</label><select name="current_status" id="shipmentStatusCodeSelect" required></select>
              <label>Açıklama</label><textarea name="description" placeholder="Kargo açıklaması"></textarea>

              <div class="form-split-2">
                <div class="admin-subcard">
                  <div class="group-title">Çıkış Rota Bilgileri</div>
                  <label>Çıkış Ülke</label><select id="shipmentOriginCountryId" name="origin_country_id" required></select>
                  <label>Çıkış Şehir</label><input name="origin_city" placeholder="Çıkış şehir" required>
                  <label>Çıkış Enlem (lat)</label><input id="shipmentOriginLat" name="origin_latitude" placeholder="Çıkış koordinatı">
                  <label>Çıkış Boylam (lng)</label><input id="shipmentOriginLng" name="origin_longitude" placeholder="Çıkış koordinatı">
                  <label class="form-label">Çıkış adresi ara</label><div class="search-row"><input id="mapSearchOriginInput" placeholder="Çıkış adresi ara"><button type="button" id="mapSearchOriginBtn">Ara</button></div>
                  <div id="mapPickerOrigin"></div>
                </div>
                <div class="admin-subcard">
                  <div class="group-title">Varış Rota Bilgileri</div>
                  <label>Varış Ülke</label><select id="shipmentDestinationCountryId" name="destination_country_id" required></select>
                  <label>Varış Şehir</label><input name="destination_city" placeholder="Varış şehir" required>
                  <label>Varış Enlem (lat)</label><input id="shipmentDestinationLat" name="destination_latitude" placeholder="Varış koordinatı">
                  <label>Varış Boylam (lng)</label><input id="shipmentDestinationLng" name="destination_longitude" placeholder="Varış koordinatı">
                  <label class="form-label">Varış adresi ara</label><div class="search-row"><input id="mapSearchDestinationInput" placeholder="Varış adresi ara"><button type="button" id="mapSearchDestinationBtn">Ara</button></div>
                  <div id="mapPickerDestination"></div>
                </div>
              </div>

              

              <div class="form-split-2">
                <div class="admin-subcard">
                  <div class="group-title">Gönderici Bilgileri</div>
                  <label>Ad Soyad</label><input name="sender_name" placeholder="Ad Soyad">
                  <label>Şirket</label><input name="sender_company" placeholder="Şirket">
                  <label>Telefon</label><input name="sender_phone" placeholder="Telefon">
                </div>
                <div class="admin-subcard">
                  <div class="group-title">Alıcı Bilgileri</div>
                  <label>Ad Soyad</label><input name="receiver_name" placeholder="Ad Soyad">
                  <label>Telefon</label><input name="receiver_phone" placeholder="Telefon">
                  <label>Adres</label><input name="receiver_address" placeholder="Adres">
                </div>
              </div>

              <button>Kargoyu Kaydet</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'countries'): $countrySub = $sub ?: 'list'; ?>
      <div class="admin-grid">
        <?php if ($countrySub === 'list'): ?>
          <div class="admin-card admin-full"><h3>Eklenen Ülkeler</h3><div class="table-wrap"><table class="list-table"><thead><tr><th>ID</th><th>Ülke</th><th>Kod</th><th>Sembol</th><th>Aksiyon</th></tr></thead><tbody id="countryTableBody"></tbody></table></div></div>
        <?php else: ?>
          <div class="admin-card"><h3>Ülke Ekle / Düzenle</h3><form id="countryForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="country_id"><label>Ülke Adı</label><input name="name" placeholder="Ülke adı" required><label>Para Birimi Kodu</label><input name="currency_code" placeholder="Para birimi kodu (USD)" required><label>Para Birimi Sembolü</label><input name="currency_symbol" placeholder="Para birimi sembolü ($)" required><label>Durum</label><select name="is_active"><option value="1">Aktif</option><option value="0">Pasif</option></select><button>Kaydet</button></form></div>
          <div class="admin-card"><h3>Ülke Çevirisi</h3><form id="countryTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><label>Ülke</label><select id="countrySelectAdmin2" name="country_id"></select><label>Dil</label><select class="lang-options" name="lang_code"></select><label>Çeviri</label><input name="name" placeholder="Çeviri ülke adı" required><button>Kaydet</button></form></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'pricing'): $psub = $sub ?: 'category-list'; ?>
      <div class="admin-grid">
      <?php if ($psub === 'category-list'): ?>
        <div class="admin-card admin-full"><table class="list-table"><thead><tr><th>ID</th><th>Kategori</th><th>Açıklama</th><th>Aksiyon</th></tr></thead><tbody id="categoryTableBody"></tbody></table></div>
      <?php elseif ($psub === 'category-new'): ?>
        <div class="admin-card"><h3>Kategori Ekle</h3><form id="categoryForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><label>Kategori Başlığı</label><input name="title" placeholder="Kategori başlığı" required><label>Açıklama</label><textarea name="description" placeholder="Açıklama"></textarea><input type="number" step="0.01" name="divisor" placeholder="Divisor" value="3000" required><input type="number" step="0.001" name="fuel_rate" placeholder="Yakıt oranı (0.10)" value="0"><input type="number" step="0.01" name="cod_fee" placeholder="COD ücret" value="0"><input type="number" step="0.01" name="min_price" placeholder="Minimum ücret" value="0"><input type="number" step="0.01" name="extra_per_kg" placeholder="Ekstra kg ücreti" value="12"><button>Kaydet</button></form></div>
        <div class="admin-card"><h3>Kategori Çevirisi</h3><form id="categoryTranslationForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><select id="categorySelectAdmin2" name="category_id"></select><select class="lang-options" name="lang_code"></select><input name="title" placeholder="Başlık" required><label>Açıklama</label><textarea name="description" placeholder="Açıklama"></textarea><button>Kaydet</button></form></div>
              <?php elseif ($psub === 'transport'): ?>
        <div class="admin-card"><h3>Taşıma Seçeneği Ekle / Düzenle</h3><form id="transportModeForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="mode_id"><label>Mode Key</label><input name="mode_key" placeholder="road" required><label>Başlık</label><input name="title" placeholder="Karayolu" required><label>Çarpan</label><input type="number" step="0.001" name="multiplier" value="1" required><label>Durum</label><select name="is_active"><option value="1">Aktif</option><option value="0">Pasif</option></select><button>Kaydet</button></form></div>
        <div class="admin-card admin-full"><table class="list-table"><thead><tr><th>ID</th><th>Key</th><th>Başlık</th><th>Çarpan</th><th>Aksiyon</th></tr></thead><tbody id="transportModeTableBody"></tbody></table></div>
      <?php elseif ($psub === 'weights'): ?>
        <div class="admin-card"><h3>Kilo Fiyatı Ekle / Düzenle</h3><form id="weightPriceForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id"><label>Taşıma Seçeneği</label><select id="weightModeId" name="transport_mode_id"></select><label>Kilo Limiti</label><input type="number" step="0.01" name="weight_limit" placeholder="1" required><label>Fiyat (USD)</label><input type="number" step="0.01" name="price_amount" placeholder="79" required><button>Kaydet</button></form></div>
        <div class="admin-card admin-full"><table class="list-table"><thead><tr><th>ID</th><th>Taşıma</th><th>Kilo Limiti</th><th>Fiyat</th><th>Aksiyon</th></tr></thead><tbody id="weightPriceTableBody"></tbody></table></div>
      <?php else: ?>
        <div class="admin-card admin-full"><h3>Fiyat Stratejisi Ekle</h3><form id="priceConfigForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><label>Ülke</label><select id="priceCountryId" name="country_id"></select><label>Kategori</label><select id="priceCategoryId" name="category_id"></select><label>Taşıma Seçeneği</label><select id="priceTransportModeId" name="transport_mode_id"></select><button>Kaydet</button></form></div>
        <div class="admin-card admin-full"><table class="list-table"><thead><tr><th>Ülke</th><th>Para Birimi</th><th>Kategori</th><th>Taşıma</th><th>Kilo Basamağı</th></tr></thead><tbody id="pricingTableBody"></tbody></table></div>
      <?php endif; ?>
      </div>
    <?php endif; ?>


    <?php if ($tab === 'documents'): $dsub = $sub ?: 'list'; ?>
      <div class="admin-grid">
        <?php if ($dsub === 'list'): ?>
          <div class="admin-card admin-full"><h3>Eklenen Belgeler</h3><div class="table-wrap"><table class="list-table"><thead><tr><th>ID</th><th>Belge Adı</th><th>Dosya</th><th>Aksiyon</th></tr></thead><tbody id="documentTableBody"></tbody></table></div></div>
        <?php else: ?>
          <div class="admin-card admin-full"><h3>Belge Ekle</h3><form id="documentForm" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="document_id" id="documentId"><label>Dil</label><select class="lang-options" name="lang_code"></select><label>Belge Adı</label><input name="title" placeholder="Belge adı" required><label>Belge Dosyası</label><input type="file" name="document_file" accept="application/pdf,image/*"><small id="documentCurrentFile"></small><button>Kaydet</button></form></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

<?php if ($tab === 'settings'): ?>
      <div class="admin-grid">
        <div class="admin-card admin-full">
          <h3>Site Ayarları</h3>
          <form id="settingsForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div class="form-split-2">
              <div class="admin-subcard">
                <div class="group-title">Genel Bilgiler</div>
                <label>Site Adı</label><input name="site_name" value="<?= htmlspecialchars($cfg['site_name'] ?? '', ENT_QUOTES) ?>" placeholder="Site adı">
                <label>Meta Başlık</label><input name="meta_title" value="<?= htmlspecialchars($cfg['meta_title'] ?? '', ENT_QUOTES) ?>" placeholder="Meta title">
                <label>Meta Açıklama</label><input name="meta_description" value="<?= htmlspecialchars($cfg['meta_description'] ?? '', ENT_QUOTES) ?>" placeholder="Meta description">
                <label>Yandex API Key</label><input name="yandex_api_key" value="<?= htmlspecialchars($cfg['yandex_api_key'] ?? '', ENT_QUOTES) ?>" placeholder="Yandex API Key">
              </div>
              <div class="admin-subcard">
                <div class="group-title">Şirket İletişim</div>
                <label>Şirket Adı</label><input name="company_name" value="<?= htmlspecialchars($cfg['company_name'] ?? '', ENT_QUOTES) ?>" placeholder="Şirket adı">
                <label>Şirket E-posta</label><input name="company_email" value="<?= htmlspecialchars($cfg['company_email'] ?? '', ENT_QUOTES) ?>" placeholder="E-posta">
                <label>Şirket Telefon</label><input name="company_phone" value="<?= htmlspecialchars($cfg['company_phone'] ?? '', ENT_QUOTES) ?>" placeholder="Telefon">
                <label>Şirket Adres</label><input name="company_address" value="<?= htmlspecialchars($cfg['company_address'] ?? '', ENT_QUOTES) ?>" placeholder="Adres">
              </div>
            </div>
            <div class="form-split-2">
              <div class="admin-subcard">
                <div class="group-title">Kurumsal Kimlik</div>
                <label>Logo Yükle</label><input type="file" name="logo_file" accept="image/*">
                <label>Favicon Yükle</label><input type="file" name="favicon_file" accept="image/*">
                <label>Logo URL</label><input name="logo_path" value="<?= htmlspecialchars($cfg['logo_path'] ?? '', ENT_QUOTES) ?>" placeholder="Logo URL">
                <label>Favicon URL</label><input name="favicon_path" value="<?= htmlspecialchars($cfg['favicon_path'] ?? '', ENT_QUOTES) ?>" placeholder="Favicon URL">
                <div class="inline-2">
                  <button type="button" id="deleteLogoBtn" class="btn-danger">Logoyu Sil</button>
                  <button type="button" id="deleteFaviconBtn" class="btn-danger">Favicon Sil</button>
                </div>
              </div>
              <div class="admin-subcard">
                <div class="group-title">Konum</div>
                <label>Şirket Enlem</label><input id="companyLat" name="company_latitude" value="<?= htmlspecialchars($cfg['company_latitude'] ?? '41.01', ENT_QUOTES) ?>" placeholder="Lat">
                <label>Şirket Boylam</label><input id="companyLng" name="company_longitude" value="<?= htmlspecialchars($cfg['company_longitude'] ?? '28.97', ENT_QUOTES) ?>" placeholder="Lng">
                <label class="form-label">Adres ara</label><div class="search-row"><input id="settingsMapSearchInput" placeholder="Adres ara"><button type="button" id="settingsMapSearchBtn">Ara</button></div>
                <div id="settingsMap"></div>
              </div>
            </div>
            <button>Kaydet</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'languages'): $langSub = $sub ?: 'list'; ?>
      <div class="admin-grid">
        <?php if ($langSub === 'list'): ?>
          <div class="admin-card admin-full"><h3>Eklenen Diller</h3><div class="table-wrap"><table class="list-table"><thead><tr><th>Kod</th><th>Dil Adı</th><th>Sıra</th><th>Durum</th><th>Aksiyon</th></tr></thead><tbody id="languageTableBody"></tbody></table></div></div>
        <?php elseif ($langSub === 'new'): ?>
          <div class="admin-card admin-full"><h3>Yeni Dil Ekle</h3><form id="languageForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><label>Dil Kodu</label><input name="code" placeholder="es" required><label>Dil Adı</label><input name="name" placeholder="Español" required><label>Sıralama</label><input name="sort_order" type="number" value="10"><label>Durum</label><select name="is_active"><option value="1">Aktif</option><option value="0">Pasif</option></select><label>JSON İçeriği (varsayılan en)</label><textarea id="newLanguageJson" rows="14" placeholder='{"en":{"front":{"key":"value"}}}'></textarea><button>Dili Kaydet</button></form></div>
        <?php else: ?>
          <div class="admin-card admin-full"><h3>Dil Düzenle</h3><form id="languageEditForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="code" id="languageEditCode"><label>Dil Adı</label><input name="name" id="languageEditName" required><label>Sıralama</label><input name="sort_order" id="languageEditSort" type="number" value="10"><label>Durum</label><select name="is_active" id="languageEditActive"><option value="1">Aktif</option><option value="0">Pasif</option></select><label>JSON İçeriği</label><textarea id="jsonEditor" name="json_payload" rows="14" placeholder='{"fr":{"front":{"hero_title":"..."}}}'></textarea><button>Dili ve Çevirileri Kaydet</button></form><button id="deleteLanguageBtn" type="button" class="btn-danger">Dili Sil</button></div>
          <div class="admin-card admin-full"><h3>Tekil Çeviri Listesi (AJAX Sayfalama)</h3><div class="filter-row"><div><label>Ara</label><input id="translationSearch" placeholder="group/key/metin ara"></div><button id="translationSearchBtn" type="button">Filtrele</button></div><div class="table-wrap"><table class="list-table"><thead><tr><th>ID</th><th>Grup</th><th>Anahtar</th><th>Metin</th><th>Aksiyon</th></tr></thead><tbody id="translationTableBody"></tbody></table></div><div class="pager-row"><button type="button" id="translationPrev">Önceki</button><span id="translationPageInfo">1 / 1</span><button type="button" id="translationNext">Sonraki</button></div></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>



    <?php if ($tab === 'admin'): ?>
      <div class="admin-grid"><div class="admin-card"><h3>Şifre Değiştir</h3><form id="adminPasswordForm"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input name="current_password" type="password" placeholder="Mevcut şifre" required><input name="new_password" type="password" placeholder="Yeni şifre" required><button>Güncelle</button></form></div></div>
    <?php endif; ?>
  </main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>window.CSRF_TOKEN = '<?= $csrf ?>';</script>
<script src="/assets/admin/admin.js"></script>
</body>
</html>
