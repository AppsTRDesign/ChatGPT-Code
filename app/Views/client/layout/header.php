<?php include __DIR__ . '/../../partials/head.php'; ?>
<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(135deg, #0a4d68, #00b8a9);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="/">NoaSoft Web Push</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#clientNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="clientNavbar">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="/client">Panel</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/client/templates">Şablonlar</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/client/notifications">Bildirimler</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/client/settings">Ayarlar</a></li>
                <li class="nav-item"><button class="btn btn-outline-light ms-lg-3" data-action="logout">Çıkış</button></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
