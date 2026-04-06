<?php declare(strict_types=1);
ob_start();
$user = $state['user'];
$resources = $state['resources'] ?? [];
$market = $state['market'] ?? [];
$countries = $state['countries'] ?? [];
$topCity = $state['top_city'] ?? null;
$nation = $state['nation'] ?? ['nation_tier' => 1, 'player_count' => 0, 'avg_city_score' => 0];
$progress = $state['progress'] ?? ['next_level_xp' => 120];
$resourceMarket = $state['resource_market'] ?? [];
$marketRules = $state['market_rules'] ?? [];
$factoryTypes = $state['factory_types'] ?? [];
$factories = $state['factories'] ?? [];
$activeWar = $state['active_war'] ?? null;
$warReports = $state['war_reports'] ?? [];
$myParty = $state['my_party'] ?? null;
$parties = $state['parties'] ?? [];
$election = $state['election'] ?? null;
$parliamentLaws = $state['parliament_laws'] ?? [];
$government = $state['government'] ?? ['roles' => [], 'actions' => []];
$myPermissions = $state['my_permissions'] ?? [];
?>
<div class="container py-3 py-md-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><span class="me-1"><?= htmlspecialchars($user['flag_emoji'], ENT_QUOTES, 'UTF-8') ?></span><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h1>
            <small class="text-secondary"><?= htmlspecialchars($user['country_name'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($user['city_name'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-shield-halved"></i> Admin</a>
            <form method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-outline-danger btn-sm">Çıkış</button></form>
        </div>
    </header>

    <section class="row g-2 mb-2">
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Enerji</p><h2 id="energy"><?= (int) $user['energy'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Seviye</p><h2 id="level"><?= (int) $user['level'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Tecrübe</p><h2 id="experience"><?= (int) $user['experience'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Kuvvet</p><h2 id="strength"><?= (int) $user['strength'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Eğitim</p><h2 id="education"><?= (int) $user['education'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Dayanıklılık</p><h2 id="endurance"><?= (int) $user['endurance'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Ulus Seviyesi</p><h2 id="nationTier"><?= (int) $nation['nation_tier'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Sonraki seviye XP</p><h2 id="nextLevelXp"><?= (int) $progress['next_level_xp'] ?></h2></div></div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Çalışma / Savaş</h2>
                <div class="d-grid gap-2">
                    <select id="resourceKey" class="form-select">
                        <?php foreach ($resources as $r): ?>
                            <option value="<?= htmlspecialchars($r['resource_key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary action-btn" data-action="work">300 Enerji ile Çalış</button>
                    <button class="btn btn-danger action-btn" data-action="battle">300 Enerji ile Savaş</button>
                </div>
                <div class="d-grid gap-2 mt-2">
                    <button class="btn btn-outline-info action-btn" data-action="upgrade" data-stat="strength">Kuvvet +1 (5 LP + 5 Gold)</button>
                    <button class="btn btn-outline-info action-btn" data-action="upgrade" data-stat="education">Eğitim +1 (5 LP + 5 Gold)</button>
                    <button class="btn btn-outline-info action-btn" data-action="upgrade" data-stat="endurance">Dayanıklılık +1 (5 LP + 5 Gold)</button>
                </div>
            </div></div>

            <div class="card panel mt-3"><div class="card-body">
                <h2 class="h6">Top Şehir Bonusu</h2>
                <?php if ($topCity): ?>
                    <p class="mb-0"><?= htmlspecialchars($topCity['country_name'] . ' / ' . $topCity['name'], ENT_QUOTES, 'UTF-8') ?> (Skor: <?= (int) $topCity['score'] ?>)</p>
                    <small class="text-secondary">Bu şehirde enerji %40 hızlı dolar, üretim %25 artar.</small>
                    <small class="text-secondary d-block mt-1">Ulus Oyuncu: <?= (int) ($nation['player_count'] ?? 0) ?> | Ortalama Şehir Skoru: <?= htmlspecialchars((string) ($nation['avg_city_score'] ?? 0), ENT_QUOTES, 'UTF-8') ?></small>

                <?php else: ?>
                    <p class="mb-0">Top şehir hesaplanamadı.</p>
                <?php endif; ?>
            </div></div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Kaynak Envanteri</h2>
                <div class="table-responsive"><table class="table table-dark table-sm align-middle mb-0"><thead><tr><th>Kaynak</th><th>Miktar</th><th>Birim</th><th>Taban Fiyat</th></tr></thead><tbody id="resourceTable">
                <?php foreach ($resources as $r): ?>
                    <tr><td><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $r['quantity'] ?></td><td><?= htmlspecialchars($r['unit'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((float) $r['base_price'], 2, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            </div></div>
        </div>
    </section>

    <section class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Global Market</h2>
                <p class="small text-secondary mb-2">
                    Alıcı vergi: %<?= htmlspecialchars((string) ($marketRules['buyer_tax_percent'] ?? 0), ENT_QUOTES, 'UTF-8') ?> •
                    Satıcı komisyon: %<?= htmlspecialchars((string) ($marketRules['seller_commission_percent'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="row g-2 mb-2">
                    <div class="col-4"><input class="form-control" id="offerResourceId" type="number" min="1" placeholder="Kaynak ID"></div>
                    <div class="col-4"><input class="form-control" id="offerQty" type="number" min="1" placeholder="Miktar"></div>
                    <div class="col-4"><input class="form-control" id="offerPrice" type="number" min="0.01" step="0.01" placeholder="Fiyat"></div>
                    <div class="col-12"><button class="btn btn-warning w-100 action-btn" data-action="market-create">İlan Aç</button></div>
                </div>
                <div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>ID</th><th>Kaynak</th><th>Satıcı</th><th>Miktar</th><th>Birim</th><th>Toplam</th><th>Vergi</th><th></th></tr></thead><tbody id="marketTable">
                <?php foreach ($market as $m): ?>
                    <tr><td><?= (int) $m['id'] ?></td><td><?= htmlspecialchars($m['resource_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($m['seller_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $m['quantity'] ?></td><td><?= number_format((float) $m['price_per_unit'], 2, ',', '.') ?></td><td><?= number_format((float) ($m['gross_total'] ?? ((float) $m['price_per_unit'] * (int) $m['quantity'])), 2, ',', '.') ?></td><td><?= number_format((float) ($m['tax_total'] ?? 0), 2, ',', '.') ?></td><td><button class="btn btn-sm btn-success action-btn" data-action="market-buy" data-offer-id="<?= (int) $m['id'] ?>">Al</button></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            </div></div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Ulus Nüfusları</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach ($countries as $c): ?>
                        <li class="list-group-item d-flex justify-content-between bg-transparent text-light"><span><?= htmlspecialchars($c['flag_emoji'] . ' ' . $c['name'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= (int) $c['player_count'] ?> oyuncu</strong></li>
                    <?php endforeach; ?>
                </ul>
            </div></div>
        </div>
    </section>

    <section class="card panel mt-3">
        <div class="card-body">
            <h2 class="h5">Dünya Haritası</h2>
            <div id="worldMap" style="height:380px"></div>
            <small class="text-secondary">Ulus oyuncu sayısı: <?= (int) $nation['player_count'] ?> • Ortalama şehir skoru: <?= htmlspecialchars((string) $nation['avg_city_score'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    </section>
    <section class="card panel mt-3">
        <div class="card-body">
            <h2 class="h5">Kaynak Piyasa Dengesi</h2>
            <div class="table-responsive">
                <table class="table table-dark table-sm">
                    <thead><tr><th>Kaynak</th><th>Fiyat</th><th>Kıtlık</th><th>Toplam Stok</th><th>Günlük Üretim</th></tr></thead>
                    <tbody id="resourceMarketTable">
                    <?php foreach ($resourceMarket as $rm): ?>
                        <tr>
                            <td><?= htmlspecialchars($rm['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float) $rm['price'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string) $rm['scarcity_factor'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $rm['total_stock'] ?></td>
                            <td><?= (int) $rm['total_yield'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card panel mt-3">
        <div class="card-body">
            <h2 class="h5">Savaş Merkezi</h2>
            <?php if ($activeWar): ?>
                <div class="alert alert-warning py-2">
                    Aktif savaş: <strong><?= htmlspecialchars($activeWar['attacker_country_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    vs
                    <strong><?= htmlspecialchars($activeWar['defender_country_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    • Skor <?= (int) $activeWar['attacker_score'] ?> - <?= (int) $activeWar['defender_score'] ?>
                </div>
                <button class="btn btn-danger action-btn" data-action="war-attack" data-war-id="<?= (int) $activeWar['id'] ?>">Cepheye Saldır (Enerji)</button>
            <?php else: ?>
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <select id="defenderCountryId" class="form-select">
                            <?php foreach ($countries as $c): ?>
                                <?php if ((int) $c['id'] === (int) $user['country_id']) { continue; } ?>
                                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['flag_emoji'] . ' ' . $c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-danger w-100 action-btn" data-action="war-start">Savaş Başlat</button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="table-responsive mt-3">
                <table class="table table-dark table-sm">
                    <thead><tr><th>Zaman</th><th>Oyuncu</th><th>Cephe</th><th>Zarar</th><th>Skor</th></tr></thead>
                    <tbody id="warReportTable">
                    <?php foreach ($warReports as $report): ?>
                        <tr>
                            <td><?= htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report['attacker_user_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report['attacker_country_name'] . ' → ' . $report['defender_country_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $report['damage'] ?></td>
                            <td><?= (int) $report['attacker_score_after'] ?> / <?= (int) $report['defender_score_after'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card panel mt-3">
        <div class="card-body">
            <h2 class="h5">Siyaset Merkezi (Parti / Seçim / Meclis)</h2>
            <div class="row g-3">
                <div class="col-lg-4">
                    <h3 class="h6">Parti Yönetimi</h3>
                    <?php if ($myParty): ?>
                        <p class="mb-2">Partin: <strong><?= htmlspecialchars($myParty['name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($myParty['my_role'], ENT_QUOTES, 'UTF-8') ?>)</p>
                        <?php if (($myParty['my_role'] ?? 'member') !== 'founder'): ?>
                            <button class="btn btn-outline-secondary btn-sm action-btn" data-action="party-leave">Partiden Ayrıl</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="d-grid gap-2">
                            <input id="partyName" class="form-control" type="text" placeholder="Parti adı">
                            <input id="partyIdeology" class="form-control" type="text" placeholder="İdeoloji">
                            <button class="btn btn-outline-info btn-sm action-btn" data-action="party-create">Parti Kur</button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive mt-2">
                        <table class="table table-dark table-sm">
                            <thead><tr><th>Parti</th><th>Üye</th><th></th></tr></thead>
                            <tbody id="partyTable">
                            <?php foreach ($parties as $party): ?>
                                <tr>
                                    <td><?= htmlspecialchars($party['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $party['member_count'] ?></td>
                                    <td><button class="btn btn-sm btn-success action-btn" data-action="party-join" data-party-id="<?= (int) $party['id'] ?>">Katıl</button></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h3 class="h6">Seçim</h3>
                    <?php if ($election): ?>
                        <p class="mb-2">Açık seçim #<?= (int) $election['id'] ?> • Bitiş: <?= htmlspecialchars($election['ends_at'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="d-grid gap-2">
                            <select id="electionPartyId" class="form-select">
                                <?php foreach (($election['parties'] ?? []) as $ep): ?>
                                    <option value="<?= (int) $ep['id'] ?>"><?= htmlspecialchars($ep['name'] . ' (' . (int) $ep['vote_count'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-warning btn-sm action-btn" data-action="election-vote" data-election-id="<?= (int) $election['id'] ?>">Seçimde Oy Ver</button>
                        </div>
                    <?php else: ?>
                        <p class="mb-2">Açık seçim yok.</p>
                        <button class="btn btn-outline-warning btn-sm action-btn" data-action="election-open">Seçim Aç</button>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <h3 class="h6">Meclis Kanunları</h3>
                    <div class="d-grid gap-2 mb-2">
                        <input id="lawTitle" class="form-control" type="text" placeholder="Kanun başlığı">
                        <textarea id="lawBody" class="form-control" rows="2" placeholder="Kanun içeriği"></textarea>
                        <button class="btn btn-outline-primary btn-sm action-btn" data-action="law-propose">Kanun Öner</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm">
                            <thead><tr><th>Kanun</th><th>Durum</th><th>Oy</th><th></th></tr></thead>
                            <tbody id="lawTable">
                            <?php foreach ($parliamentLaws as $law): ?>
                                <tr>
                                    <td><?= htmlspecialchars($law['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($law['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $law['yes_votes'] ?> / <?= (int) $law['no_votes'] ?></td>
                                    <td>
                                        <?php if (($law['status'] ?? 'open') === 'open'): ?>
                                            <button class="btn btn-sm btn-success action-btn" data-action="law-vote" data-law-id="<?= (int) $law['id'] ?>" data-vote="yes">Evet</button>
                                            <button class="btn btn-sm btn-danger action-btn" data-action="law-vote" data-law-id="<?= (int) $law['id'] ?>" data-vote="no">Hayır</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card panel mt-3">
        <div class="card-body">
            <h2 class="h5">Bakanlık Rolleri ve Yetki Akışları</h2>
            <p class="small text-secondary mb-2">Yetkilerin: <?= htmlspecialchars(implode(', ', $myPermissions) ?: 'yok', ENT_QUOTES, 'UTF-8') ?></p>

            <div class="row g-3">
                <div class="col-lg-4">
                    <h3 class="h6">Rol Atama (Başkan)</h3>
                    <div class="d-grid gap-2">
                        <input id="govTargetUserId" class="form-control" type="number" min="1" placeholder="Hedef Oyuncu ID">
                        <select id="govRoleKey" class="form-select">
                            <option value="minister_economy">Ekonomi Bakanı</option>
                            <option value="minister_defense">Savunma Bakanı</option>
                            <option value="minister_interior">İçişleri Bakanı</option>
                        </select>
                        <button class="btn btn-outline-light btn-sm action-btn" data-action="gov-assign-role">Rol Ata</button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h3 class="h6">Ekonomi Aksiyonu</h3>
                    <div class="d-grid gap-2">
                        <input id="govBuyerTax" class="form-control" type="number" min="0" max="30" step="0.1" placeholder="Alıcı vergi %">
                        <input id="govSellerCommission" class="form-control" type="number" min="0" max="30" step="0.1" placeholder="Satıcı komisyon %">
                        <button class="btn btn-outline-warning btn-sm action-btn" data-action="gov-market-tax">Pazar Vergisini Güncelle</button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h3 class="h6">Savunma Aksiyonu</h3>
                    <div class="d-grid gap-2">
                        <input id="govWarScoreToWin" class="form-control" type="number" min="200" max="10000" step="10" placeholder="Savaş Skor Hedefi">
                        <button class="btn btn-outline-danger btn-sm action-btn" data-action="gov-war-score">Savaş Hedefini Güncelle</button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <h3 class="h6">Aktif Roller</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm">
                            <thead><tr><th>Rol</th><th>Oyuncu</th><th>Atanma</th></tr></thead>
                            <tbody id="governmentRoleTable">
                            <?php foreach (($government['roles'] ?? []) as $gr): ?>
                                <tr>
                                    <td><?= htmlspecialchars($gr['role_key'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($gr['username'], ENT_QUOTES, 'UTF-8') ?> (#<?= (int) $gr['user_id'] ?>)</td>
                                    <td><?= htmlspecialchars($gr['assigned_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h3 class="h6">Bakanlık Aksiyon Logu</h3>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm">
                            <thead><tr><th>Zaman</th><th>Aktör</th><th>Aksiyon</th></tr></thead>
                            <tbody id="governmentActionTable">
                            <?php foreach (($government['actions'] ?? []) as $ga): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ga['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($ga['actor_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ga['role_key'], ENT_QUOTES, 'UTF-8') ?>)</td>
                                    <td><?= htmlspecialchars($ga['action_key'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast text-bg-dark border-0"><div class="d-flex"><div class="toast-body" id="toastBody">Hazır.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>
</div>

<script>
window.GAME_BOOTSTRAP = <?= json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Dashboard';
require base_path('views/layouts/base.php');
