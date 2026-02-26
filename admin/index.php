<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$tab = $_GET['tab'] ?? 'dashboard';
?><!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<section class="container section">
  <h1>Pro Admin Yönetimi</h1>
  <p><a href="/admin/logout.php">Çıkış</a></p>
  <nav class="desktop-menu" style="display:flex;flex-wrap:wrap">
    <a href="?tab=dashboard">Dashboard</a>
    <a href="?tab=pages">Sayfalar</a>
    <a href="?tab=menus">Menüler</a>
    <a href="?tab=shipments">Kargo Takip</a>
    <a href="?tab=pricing">Fiyatlama</a>
    <a href="?tab=settings">Site Ayarları</a>
    <a href="?tab=languages">Diller</a>
  </nav>

  <?php if ($tab === 'dashboard'): ?>
    <div class="grid-2">
      <div class="panel"><h3>Toplam Kargo</h3><strong><?= (int) db()->query('SELECT COUNT(*) FROM shipments')->fetchColumn() ?></strong></div>
      <div class="panel"><h3>Toplam Sayfa</h3><strong><?= (int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn() ?></strong></div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'pages'): ?>
    <div class="panel">
      <h3>Sayfa Ekle / Güncelle</h3>
      <form id="pageForm">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="number" name="page_id" placeholder="Sayfa ID (güncelleme)">
        <input type="text" name="slug_source" placeholder="Başlık (slug üretmek için)" required>
        <select name="lang_code" required><?php foreach (SUPPORTED_LANGS as $l): ?><option><?= $l ?></option><?php endforeach; ?></select>
        <input type="text" name="title" placeholder="Başlık" required>
        <textarea name="content_html" rows="8" placeholder="HTML içerik" required></textarea>
        <button type="submit">Kaydet</button>
      </form>
      <div id="pagesList"></div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'menus'): ?>
    <div class="panel">
      <h3>Dinamik Menü Yönetimi</h3>
      <form id="menuForm"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input name="page_id" type="number" required placeholder="Sayfa ID"><input name="sort_order" type="number" value="1"><button>Ekle</button></form>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'shipments'): ?>
    <div class="panel">
      <h3>Kargo Takip Numarası Oluşturma</h3>
      <form id="shipmentForm">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input name="tracking_number" placeholder="Tracking no" required>
        <input name="origin_country" placeholder="Çıkış Ülke" required>
        <input name="origin_city" placeholder="Çıkış Şehir" required>
        <input name="destination_country" placeholder="Varış Ülke" required>
        <input name="destination_city" placeholder="Varış Şehir" required>
        <input name="current_status" placeholder="Durum" required>
        <textarea name="description" placeholder="Açıklama"></textarea>
        <input name="sender_name" placeholder="Gönderici ad/soyad">
        <input name="sender_company" placeholder="Gönderici şirket">
        <input name="sender_phone" placeholder="Gönderici telefon">
        <input name="receiver_name" placeholder="Alıcı ad/soyad">
        <input name="receiver_phone" placeholder="Alıcı telefon">
        <input name="receiver_address" placeholder="Alıcı adres">
        <button>Kargo Kaydet</button>
      </form>
      <h4>Durum Güncelle</h4>
      <form id="eventForm"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input name="tracking_number" placeholder="Tracking no" required><input name="status_code" placeholder="status code" required><input name="status_note" placeholder="Açıklama"><input name="country" placeholder="Ülke"><input name="city" placeholder="Şehir"><input name="latitude" placeholder="Lat"><input name="longitude" placeholder="Lng"><button>Durum Ekle</button></form>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'pricing'): ?>
    <div class="grid-2">
      <form id="countryForm" class="panel"><h3>Ülke Ekle</h3><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input name="name" placeholder="Ülke adı" required><button>Kaydet</button></form>
      <form id="categoryForm" class="panel"><h3>Kategori Ekle</h3><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input name="title" placeholder="Kategori" required><textarea name="description" placeholder="Açıklama"></textarea><button>Kaydet</button></form>
    </div>
    <form id="priceConfigForm" class="panel"><h3>Ülke + Kategori Ücret</h3><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input name="country_id" placeholder="Ülke ID" required><input name="category_id" placeholder="Kategori ID" required><input name="price_amount" placeholder="Fiyat" required><button>Kaydet</button></form>
  <?php endif; ?>

  <?php if ($tab === 'settings'): ?>
    <?php $cfg = settings(); ?>
    <form id="settingsForm" class="panel">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input name="site_name" value="<?= htmlspecialchars($cfg['site_name'] ?? '', ENT_QUOTES) ?>" placeholder="Site adı">
      <input name="company_name" value="<?= htmlspecialchars($cfg['company_name'] ?? '', ENT_QUOTES) ?>" placeholder="Şirket adı">
      <input name="company_email" value="<?= htmlspecialchars($cfg['company_email'] ?? '', ENT_QUOTES) ?>" placeholder="E-posta">
      <input name="company_phone" value="<?= htmlspecialchars($cfg['company_phone'] ?? '', ENT_QUOTES) ?>" placeholder="Telefon">
      <input name="company_address" value="<?= htmlspecialchars($cfg['company_address'] ?? '', ENT_QUOTES) ?>" placeholder="Adres">
      <input name="meta_title" value="<?= htmlspecialchars($cfg['meta_title'] ?? '', ENT_QUOTES) ?>" placeholder="Meta title">
      <input name="meta_description" value="<?= htmlspecialchars($cfg['meta_description'] ?? '', ENT_QUOTES) ?>" placeholder="Meta description">
      <input name="logo_path" value="<?= htmlspecialchars($cfg['logo_path'] ?? '', ENT_QUOTES) ?>" placeholder="Logo URL">
      <input name="favicon_path" value="<?= htmlspecialchars($cfg['favicon_path'] ?? '', ENT_QUOTES) ?>" placeholder="Favicon URL">
      <input name="osm_embed_url" value="<?= htmlspecialchars($cfg['osm_embed_url'] ?? '', ENT_QUOTES) ?>" placeholder="OSM Embed URL">
      <button>Kaydet</button>
    </form>
  <?php endif; ?>

  <?php if ($tab === 'languages'): ?>
    <form id="langForm" class="panel"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input name="lang_code" placeholder="en"><input name="group_name" placeholder="front"><input name="key_name" placeholder="hero_title"><textarea name="text_value" placeholder="Metin"></textarea><button>Çeviri Kaydet</button></form>
  <?php endif; ?>

</section>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
const endpoint = (name) => '/admin/api/' + name + '.php';
const ajaxForm = (id, api) => $(id).on('submit', function(e){e.preventDefault();$.post(endpoint(api), $(this).serialize(), function(res){res.ok?toastr.success(res.message):toastr.error(res.message);}, 'json');});
ajaxForm('#pageForm','page_save');ajaxForm('#menuForm','menu_save');ajaxForm('#shipmentForm','shipment_save');ajaxForm('#eventForm','event_save');ajaxForm('#countryForm','country_save');ajaxForm('#categoryForm','category_save');ajaxForm('#priceConfigForm','price_save');ajaxForm('#settingsForm','settings_save');ajaxForm('#langForm','lang_save');
</script>
</body>
</html>
