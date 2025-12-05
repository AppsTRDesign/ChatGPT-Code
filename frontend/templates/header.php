<?php
require_once __DIR__ . '/../helpers.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= $meta ?? render_meta('Maps Portal'); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/frontend/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="/">
      <span class="brand-icon me-2">🗺️</span>
      <span class="fw-bold">Maps Portal</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/">Anasayfa</a></li>
        <li class="nav-item"><a class="nav-link" href="/kategoriler">Kategoriler</a></li>
        <li class="nav-item"><a class="nav-link" href="/populer">Popüler</a></li>
        <li class="nav-item"><a class="nav-link" href="/en-cok-ziyaret-edilen">En Çok Görüntülenen</a></li>
        <li class="nav-item"><a class="nav-link" href="/en-cok-yorum-alan">En Çok Yorum Alan</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="py-4">
<div class="container-fluid">
