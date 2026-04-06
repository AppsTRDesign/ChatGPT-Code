(function () {
    'use strict';

    const cfg = window.APP_CONFIG || {};
    const bootstrapData = window.GAME_BOOTSTRAP || {};
    const csrf = cfg.csrf || '';
    const apiBase = cfg.apiBase || '/api/v1';
    const toastEl = document.getElementById('appToast');
    const toastBody = document.getElementById('toastBody');
    const bsToast = toastEl ? new bootstrap.Toast(toastEl) : null;

    const showToast = (message) => {
        if (!message || !bsToast || !toastBody) return;
        toastBody.textContent = message;
        bsToast.show();
    };

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = Number(value || 0).toLocaleString('tr-TR');
    };

    const renderResources = (rows) => {
        const tbody = document.getElementById('resourceTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td>${r.name}</td>
                <td>${Number(r.quantity).toLocaleString('tr-TR')}</td>
                <td>${r.unit}</td>
                <td>${Number(r.base_price).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            </tr>
        `).join('');
    };

    const renderMarket = (rows) => {
        const tbody = document.getElementById('marketTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((m) => `
            <tr>
                <td>${m.id}</td>
                <td>${m.resource_name}</td>
                <td>${m.seller_name}</td>
                <td>${Number(m.quantity).toLocaleString('tr-TR')}</td>
                <td>${Number(m.price_per_unit).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td>${Number(m.gross_total || (Number(m.quantity) * Number(m.price_per_unit))).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td>${Number(m.tax_total || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td><button class="btn btn-sm btn-success action-btn" data-action="market-buy" data-offer-id="${m.id}">Al</button></td>
            </tr>
        `).join('');
    };


    const renderResourceMarket = (rows) => {
        const tbody = document.getElementById('resourceMarketTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((rm) => `
            <tr>
                <td>${rm.name}</td>
                <td>${Number(rm.price).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td>${rm.scarcity_factor}</td>
                <td>${Number(rm.total_stock).toLocaleString('tr-TR')}</td>
                <td>${Number(rm.total_yield).toLocaleString('tr-TR')}</td>
            </tr>
        `).join('');
    };


    const renderFactories = (rows) => {
        const tbody = document.getElementById('factoryTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((f) => `
            <tr>
                <td>${f.id}</td>
                <td>${f.factory_name}</td>
                <td>${f.level}</td>
                <td>${f.workers}</td>
                <td>${f.last_production_at || '-'}</td>
                <td><button class="btn btn-sm btn-warning action-btn" data-action="factory-produce" data-factory-id="${f.id}">Üretim</button></td>
            </tr>
        `).join('');
    };

    const renderWarReports = (rows) => {
        const tbody = document.getElementById('warReportTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td>${r.created_at}</td>
                <td>${r.attacker_user_name}</td>
                <td>${r.attacker_country_name} → ${r.defender_country_name}</td>
                <td>${Number(r.damage).toLocaleString('tr-TR')}</td>
                <td>${Number(r.attacker_score_after).toLocaleString('tr-TR')} / ${Number(r.defender_score_after).toLocaleString('tr-TR')}</td>
            </tr>
        `).join('');
    };

    const renderParties = (rows) => {
        const tbody = document.getElementById('partyTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((p) => `
            <tr>
                <td>${p.name}</td>
                <td>${Number(p.member_count).toLocaleString('tr-TR')}</td>
                <td><button class="btn btn-sm btn-success action-btn" data-action="party-join" data-party-id="${p.id}">Katıl</button></td>
            </tr>
        `).join('');
    };

    const renderLaws = (rows) => {
        const tbody = document.getElementById('lawTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((law) => `
            <tr>
                <td>${law.title}</td>
                <td>${law.status}</td>
                <td>${Number(law.yes_votes).toLocaleString('tr-TR')} / ${Number(law.no_votes).toLocaleString('tr-TR')}</td>
                <td>
                    ${law.status === 'open'
                        ? `<button class="btn btn-sm btn-success action-btn" data-action="law-vote" data-law-id="${law.id}" data-vote="yes">Evet</button>
                           <button class="btn btn-sm btn-danger action-btn" data-action="law-vote" data-law-id="${law.id}" data-vote="no">Hayır</button>`
                        : ''}
                </td>
            </tr>
        `).join('');
    };

    const renderGovernmentRoles = (rows) => {
        const tbody = document.getElementById('governmentRoleTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td>${r.role_key}</td>
                <td>${r.username} (#${r.user_id})</td>
                <td>${r.assigned_at}</td>
            </tr>
        `).join('');
    };

    const renderGovernmentActions = (rows) => {
        const tbody = document.getElementById('governmentActionTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((a) => `
            <tr>
                <td>${a.created_at}</td>
                <td>${a.actor_name} (${a.role_key})</td>
                <td>${a.action_key}</td>
            </tr>
        `).join('');
    };

    const renderTravelPermits = (rows) => {
        const tbody = document.getElementById('travelPermitTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((p) => `
            <tr>
                <td>${p.id}</td>
                <td>${p.from_country_name} → ${p.to_country_name}</td>
                <td>${p.status}</td>
                <td>${Number(p.visa_fee).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td>${p.valid_until || p.requested_at}</td>
                <td>${p.violation_reason || '-'}</td>
            </tr>
        `).join('');
    };

    const renderCitizenshipRequests = (rows) => {
        const tbody = document.getElementById('citizenshipTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td>${r.id}</td>
                <td>${r.from_country_name} → ${r.to_country_name}</td>
                <td>${r.status}</td>
                <td>${r.decided_at || r.requested_at}</td>
            </tr>
        `).join('');
    };

    const renderRankings = (data) => {
        const playerBody = document.getElementById('rankingPlayerTable');
        const cityBody = document.getElementById('rankingCityTable');
        const countryBody = document.getElementById('rankingCountryTable');
        if (playerBody) {
            playerBody.innerHTML = (data?.players || []).map((p, i) => `
                <tr><td>#${i + 1}</td><td>${p.username}</td><td>${Number(p.level).toLocaleString('tr-TR')}</td><td>${Number(p.experience).toLocaleString('tr-TR')}</td><td>${Number(p.war_power).toLocaleString('tr-TR')}</td></tr>
            `).join('');
        }
        if (cityBody) {
            cityBody.innerHTML = (data?.cities || []).map((c, i) => `
                <tr><td>#${i + 1}</td><td>${c.country_name} / ${c.name}</td><td>${Number(c.score).toLocaleString('tr-TR')}</td><td>${Number(c.player_count).toLocaleString('tr-TR')}</td></tr>
            `).join('');
        }
        if (countryBody) {
            countryBody.innerHTML = (data?.countries || []).map((c, i) => `
                <tr><td>#${i + 1}</td><td>${c.flag_emoji || ''} ${c.name}</td><td>${Number(c.player_count).toLocaleString('tr-TR')}</td><td>${Number(c.avg_city_score || 0).toLocaleString('tr-TR', { maximumFractionDigits: 2 })}</td></tr>
            `).join('');
        }
    };

    const renderDailyQuests = (rows) => {
        const tbody = document.getElementById('dailyQuestTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((q) => `
            <tr>
                <td>${q.title}</td>
                <td>${Number(q.progress_value).toLocaleString('tr-TR')} / ${Number(q.target_value).toLocaleString('tr-TR')}</td>
                <td>${Number(q.reward_gold).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} + ${Number(q.reward_xp).toLocaleString('tr-TR')} XP</td>
                <td>${q.status}</td>
                <td>${q.status === 'completed' ? `<button class="btn btn-sm btn-success action-btn" data-action="quest-claim" data-quest-id="${q.id}">Ödülü Al</button>` : '-'}</td>
            </tr>
        `).join('');
    };

    const renderAchievements = (rows) => {
        const tbody = document.getElementById('achievementTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((a) => `
            <tr>
                <td>${a.title}</td>
                <td>${a.description}</td>
                <td>${a.target_value}</td>
                <td>${Number(a.reward_gold).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} + ${Number(a.reward_xp).toLocaleString('tr-TR')} XP</td>
                <td>${Number(a.unlocked) === 1 ? 'Açıldı' : 'Kilitli'}</td>
            </tr>
        `).join('');
    };

    const renderNotifications = (rows) => {
        const tbody = document.getElementById('notificationTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((n) => `
            <tr>
                <td>${n.created_at}</td>
                <td>${n.title}</td>
                <td>${n.body}</td>
                <td>${Number(n.is_read) === 1 ? 'Okundu' : '<button class="btn btn-sm btn-outline-info action-btn" data-action="notification-read" data-notification-id="' + n.id + '">Okundu işaretle</button>'}</td>
            </tr>
        `).join('');
    };

    const renderEventFeed = (rows) => {
        const tbody = document.getElementById('eventFeedTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((e) => `
            <tr>
                <td>${e.created_at}</td>
                <td>${e.event_type}</td>
                <td>${e.title}</td>
                <td>${e.body}</td>
            </tr>
        `).join('');
    };

    const renderUpgradeQueue = (rows) => {
        const tbody = document.getElementById('upgradeQueueTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((q) => `
            <tr><td>${q.stat_key}</td><td>${q.target_value}</td><td>${q.ready_at}</td><td>${q.status}</td></tr>
        `).join('');
    };

    const renderTravelerOffers = (rows) => {
        const tbody = document.getElementById('travelerOfferTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((o) => `
            <tr><td>${o.title}</td><td>${o.starts_at}</td><td>${o.ends_at}</td></tr>
        `).join('');
    };

    const renderProvinces = (rows) => {
        const tbody = document.getElementById('myProvinceTable');
        if (!tbody) return;
        tbody.innerHTML = rows.map((p) => `
            <tr><td>${p.id}</td><td>${p.name}</td><td>${p.country_name}</td><td>${p.color_hex}</td><td>${p.protection_until || '-'}</td></tr>
        `).join('');
    };

    const renderJobStatus = (job, cityFactory) => {
        const el = document.getElementById('jobStatus');
        if (!el) return;

        if (job) {
            el.innerHTML = `Aktif işin: <strong>${job.factory_name || 'Yönetim Fabrikası'}</strong>`;
            return;
        }
        if (cityFactory) {
            el.innerHTML = `Bu şehirde çalışabilmek için önce <strong>${cityFactory.name || 'Yönetim Fabrikası'}</strong> için işe başlamalısın.`;
            return;
        }
        el.textContent = 'Bu şehirde aktif yönetim fabrikası yok. Yönetici panelinden fabrika açılması gerekiyor.';
    };

    const syncStats = (payload) => {
        const user = payload?.data?.user;
        if (!user) return;
        setText('energy', user.energy);
        setText('level', user.level);
        setText('experience', user.experience);
        setText('strength', user.strength);
        setText('education', user.education);
        setText('endurance', user.endurance);
        setText('nationTier', payload?.data?.nation?.nation_tier || 1);
        renderResources(payload.data.resources || []);
        renderMarket(payload.data.market || []);
        renderResourceMarket(payload.data.resource_market || []);
        renderFactories(payload.data.factories || []);
        renderWarReports(payload.data.war_reports || []);
        renderParties(payload.data.parties || []);
        renderLaws(payload.data.parliament_laws || []);
        renderGovernmentRoles(payload?.data?.government?.roles || []);
        renderGovernmentActions(payload?.data?.government?.actions || []);
        renderTravelPermits(payload.data.travel_permits || []);
        renderCitizenshipRequests(payload.data.citizenship_requests || []);
        renderRankings(payload.data.rankings || {});
        renderDailyQuests(payload.data.daily_quests || []);
        renderAchievements(payload.data.achievements || []);
        renderNotifications(payload.data.notifications || []);
        renderEventFeed(payload.data.event_feed || []);
        renderUpgradeQueue(payload.data.upgrade_queue || []);
        renderTravelerOffers(payload.data.traveler_offers || []);
        renderProvinces(payload.data.my_provinces || []);
        renderJobStatus(payload.data.government_factory_job || null, payload.data.government_factory_city || null);
    };

    const refreshState = async () => {
        const res = await fetch(`${apiBase}/state`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return;
        const payload = await res.json();
        if (payload.ok) {
            syncStats(payload);
        }
    };

    const post = async (url, data) => {
        const fd = new FormData();
        fd.append('_csrf', csrf);
        Object.entries(data || {}).forEach(([k, v]) => fd.append(k, String(v)));
        const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        return res.json();
    };

    const onAction = async (action, button) => {
        if (action === 'work') {
            const resource = document.getElementById('resourceKey')?.value || 'gold';
            return post(`${apiBase}/action/work`, { resource });
        }
        if (action === 'battle') {
            return post(`${apiBase}/action/battle`, {});
        }
        if (action === 'gov-job-start') {
            return post(`${apiBase}/gov-factory/job/start`, {});
        }
        if (action === 'gov-job-leave') {
            return post(`${apiBase}/gov-factory/job/leave`, {});
        }
        if (action === 'upgrade') {
            return post(`${apiBase}/action/upgrade`, { stat: button.dataset.stat || 'strength' });
        }
        if (action === 'market-create') {
            return post(`${apiBase}/market/create`, {
                resource_id: document.getElementById('offerResourceId')?.value || 0,
                quantity: document.getElementById('offerQty')?.value || 0,
                price_per_unit: document.getElementById('offerPrice')?.value || 0,
            });
        }
        if (action === 'market-buy') {
            return post(`${apiBase}/market/buy`, { offer_id: button.dataset.offerId || 0 });
        }
        if (action === 'factory-create') {
            return post(`${apiBase}/factory/create`, { factory_type_id: document.getElementById('factoryTypeId')?.value || 0 });
        }
        if (action === 'factory-produce') {
            return post(`${apiBase}/factory/produce`, { factory_id: button.dataset.factoryId || 0 });
        }
        if (action === 'war-start') {
            return post(`${apiBase}/war/start`, { defender_country_id: document.getElementById('defenderCountryId')?.value || 0 });
        }
        if (action === 'war-attack') {
            return post(`${apiBase}/war/attack`, { war_id: button.dataset.warId || 0 });
        }
        if (action === 'party-create') {
            return post(`${apiBase}/party/create`, {
                name: document.getElementById('partyName')?.value || '',
                ideology: document.getElementById('partyIdeology')?.value || '',
            });
        }
        if (action === 'party-join') {
            return post(`${apiBase}/party/join`, { party_id: button.dataset.partyId || 0 });
        }
        if (action === 'party-leave') {
            return post(`${apiBase}/party/leave`, {});
        }
        if (action === 'election-open') {
            return post(`${apiBase}/election/open`, {});
        }
        if (action === 'election-vote') {
            return post(`${apiBase}/election/vote`, {
                election_id: button.dataset.electionId || 0,
                party_id: document.getElementById('electionPartyId')?.value || 0,
            });
        }
        if (action === 'law-propose') {
            return post(`${apiBase}/law/propose`, {
                title: document.getElementById('lawTitle')?.value || '',
                body: document.getElementById('lawBody')?.value || '',
            });
        }
        if (action === 'law-vote') {
            return post(`${apiBase}/law/vote`, {
                law_id: button.dataset.lawId || 0,
                vote: button.dataset.vote || '',
            });
        }
        if (action === 'gov-assign-role') {
            return post(`${apiBase}/gov/assign-role`, {
                target_user_id: document.getElementById('govTargetUserId')?.value || 0,
                role_key: document.getElementById('govRoleKey')?.value || '',
            });
        }
        if (action === 'gov-market-tax') {
            return post(`${apiBase}/gov/action`, {
                action_key: 'market.adjust_tax',
                buyer_tax_percent: document.getElementById('govBuyerTax')?.value || 0,
                seller_commission_percent: document.getElementById('govSellerCommission')?.value || 0,
            });
        }
        if (action === 'gov-war-score') {
            return post(`${apiBase}/gov/action`, {
                action_key: 'war.adjust_score_to_win',
                score_to_win: document.getElementById('govWarScoreToWin')?.value || 1000,
            });
        }
        if (action === 'travel-request') {
            return post(`${apiBase}/travel/request-permit`, { to_country_id: document.getElementById('travelCountryId')?.value || 0 });
        }
        if (action === 'travel-permit-decision') {
            return post(`${apiBase}/travel/permit-decision`, {
                permit_id: document.getElementById('travelPermitId')?.value || 0,
                decision: button.dataset.decision || '',
            });
        }
        if (action === 'travel-move') {
            return post(`${apiBase}/travel/move`, { city_id: document.getElementById('travelCityId')?.value || 0 });
        }
        if (action === 'citizenship-request') {
            return post(`${apiBase}/travel/request-citizenship`, { to_country_id: document.getElementById('citizenshipCountryId')?.value || 0 });
        }
        if (action === 'citizenship-decision') {
            return post(`${apiBase}/travel/citizenship-decision`, {
                request_id: document.getElementById('citizenshipRequestId')?.value || 0,
                decision: button.dataset.decision || '',
            });
        }
        if (action === 'quest-claim') {
            return post(`${apiBase}/quest/claim`, { quest_id: button.dataset.questId || 0 });
        }
        if (action === 'notification-read') {
            return post(`${apiBase}/notification/read`, { notification_id: button.dataset.notificationId || 0 });
        }
        if (action === 'coup-start') {
            return post(`${apiBase}/coup/start`, {
                country_id: document.getElementById('coupCountryId')?.value || 0,
                type: document.getElementById('coupType')?.value || 'coup',
                gold: document.getElementById('coupGold')?.value || 0,
            });
        }
        if (action === 'province-donate') {
            return post(`${apiBase}/province/donate`, {
                province_id: document.getElementById('provinceId')?.value || 0,
                target_user_id: document.getElementById('provinceTargetUserId')?.value || 0,
            });
        }
        if (action === 'province-identity') {
            return post(`${apiBase}/province/update-identity`, {
                province_id: document.getElementById('provinceId')?.value || 0,
                name: document.getElementById('provinceName')?.value || '',
                color_hex: document.getElementById('provinceColorHex')?.value || '#0D6EFD',
                flag_path: document.getElementById('provinceFlagPath')?.value || '',
            });
        }
        return { ok: false, message: 'Bilinmeyen eylem.' };
    };

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.action-btn');
        if (!button) return;

        try {
            const result = await onAction(button.dataset.action, button);
            showToast(result.message || 'İşlem tamamlandı');
            await refreshState();
        } catch (e) {
            showToast('İşlem sırasında hata oluştu');
        }
    });

    const loadScript = (src) => new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Script load failed: ${src}`));
        document.head.appendChild(script);
    });

    const colorByRatio = (ratio) => {
        const clamped = Math.max(0, Math.min(1, ratio));
        const red = Math.round(33 + (clamped * 185));
        const green = Math.round(180 - (clamped * 100));
        const blue = Math.round(255 - (clamped * 175));
        return `rgb(${red}, ${green}, ${blue})`;
    };

    const aggregateResourceYield = (rows) => {
        const sum = {};
        rows.forEach((row) => {
            const code = String(row.code || '').toUpperCase();
            sum[code] = (sum[code] || 0) + Number(row.daily_yield || 0);
        });
        return sum;
    };

    const initMap = async () => {
        const mapElement = document.getElementById('worldMap');
        if (!mapElement) return;
        const mapLegend = document.getElementById('mapLegend');

        const renderMapFallback = () => {
            const rows = bootstrapData.countries || [];
            const topRows = [...rows].sort((a, b) => Number(b.player_count || 0) - Number(a.player_count || 0)).slice(0, 8);
            mapElement.innerHTML = `
                <div class="alert alert-warning small mb-2">İnteraktif harita yüklenemedi. Fallback ülke listesi gösteriliyor.</div>
                <div class="table-responsive">
                    <table class="table table-dark table-sm mb-0">
                        <thead><tr><th>Ülke</th><th>Oyuncu</th></tr></thead>
                        <tbody>${topRows.map((r) => `<tr><td>${r.flag_emoji || ''} ${r.name}</td><td>${Number(r.player_count || 0).toLocaleString('tr-TR')}</td></tr>`).join('')}</tbody>
                    </table>
                </div>
            `;
            if (mapLegend) {
                mapLegend.textContent = 'Fallback görünümü aktif.';
            }
        };

        try {
            if (typeof window.jsVectorMap === 'undefined') {
                await loadScript('https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/js/jsvectormap.min.js');
                await loadScript('https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/maps/world.js');
            }

            if (typeof window.jsVectorMap === 'undefined') {
                renderMapFallback();
                return;
            }

            const mapCities = bootstrapData.map?.cities || [];
            const mapPois = bootstrapData.map?.pois || [];
            const countryRows = bootstrapData.countries || [];
            const layerRows = bootstrapData.map?.layers || [];
            const resourceRows = bootstrapData.map?.country_resources || [];

            const cityMarkers = mapCities.map((city) => ({
                name: `${city.country_name} / ${city.name} (${city.player_count})`,
                coords: [Number(city.lat), Number(city.lng)],
                type: 'city',
                countryCode: String(city.country_code || '').toUpperCase(),
            }));

            const poiMarkers = mapPois.map((poi) => ({
                name: `${poi.country_name} / ${poi.city_name} - ${poi.title}`,
                coords: [Number(poi.lat), Number(poi.lng)],
                type: 'poi',
                countryCode: String(poi.country_code || '').toUpperCase(),
            }));
            const allMarkers = [...cityMarkers, ...poiMarkers];
            const mapLayerSelect = document.getElementById('mapLayerSelect');
            const markerFilterSelect = document.getElementById('mapMarkerFilter');
            const mapCountryDetail = document.getElementById('mapCountryDetail');
            const userCountryCode = String(bootstrapData.user?.country_code || '').toUpperCase();

            const populationValues = {};
            countryRows.forEach((row) => {
                const code = String(row.code || '').toUpperCase();
                populationValues[code] = Number(row.player_count || 0);
            });

            const resourceYieldValues = aggregateResourceYield(resourceRows);

            const getLayerData = (layerKey) => {
                if (layerKey === 'none') {
                    return { values: {}, scale: {}, legend: 'Katman kapalı.' };
                }

                if (layerKey === 'population') {
                    const values = { ...populationValues };
                    const max = Math.max(1, ...Object.values(values));
                    const scale = {};
                    Object.values(values).forEach((v) => {
                        scale[v] = colorByRatio(v / max);
                    });
                    return { values, scale, legend: 'Oyuncu yoğunluğu katmanı: açık ton daha fazla oyuncu.' };
                }

                if (layerKey === 'resource_total_yield') {
                    const values = { ...resourceYieldValues };
                    const max = Math.max(1, ...Object.values(values));
                    const scale = {};
                    Object.values(values).forEach((v) => {
                        scale[v] = colorByRatio(v / max);
                    });
                    return { values, scale, legend: 'Toplam günlük üretim katmanı: açık ton daha yüksek üretim.' };
                }

                const values = {};
                const scale = {};
                layerRows
                    .filter((row) => row.layer_key === layerKey)
                    .forEach((row) => {
                        const code = String(row.country_code || '').toUpperCase();
                        const intensity = Number(row.intensity || 1);
                        values[code] = intensity;
                        scale[intensity] = row.color_hex || '#0d6efd';
                    });
                return { values, scale, legend: `${layerKey} katmanı aktif.` };
            };

            const renderCountryDetail = (code) => {
                if (!mapCountryDetail) return;
                const normalizedCode = String(code || '').toUpperCase();
                if (!normalizedCode) {
                    mapCountryDetail.textContent = 'Haritadan bir ülkeye tıklayarak detayları görüntüleyin.';
                    return;
                }

                const country = countryRows.find((c) => String(c.code || '').toUpperCase() === normalizedCode);
                const countryCities = mapCities.filter((c) => String(c.country_code || '').toUpperCase() === normalizedCode);
                const countryPois = mapPois.filter((p) => String(p.country_code || '').toUpperCase() === normalizedCode);
                const totalYield = Object.entries(resourceYieldValues)
                    .filter(([countryCode]) => countryCode === normalizedCode)
                    .reduce((sum, [, value]) => sum + Number(value || 0), 0);

                if (!country) {
                    mapCountryDetail.textContent = `${normalizedCode} kodlu ülke için kayıtlı veri bulunamadı.`;
                    return;
                }

                const topCities = [...countryCities]
                    .sort((a, b) => Number(b.player_count || 0) - Number(a.player_count || 0))
                    .slice(0, 3)
                    .map((c) => `${c.name} (${Number(c.player_count || 0)})`)
                    .join(', ') || '-';

                mapCountryDetail.innerHTML = `
                    <strong>${country.flag_emoji || '🏳️'} ${country.name}</strong>
                    <div class="small text-secondary">
                        Oyuncu: ${Number(country.player_count || 0).toLocaleString('tr-TR')} •
                        Şehir: ${countryCities.length} •
                        POI: ${countryPois.length} •
                        Günlük üretim: ${Number(totalYield).toLocaleString('tr-TR')}
                    </div>
                    <div class="small mt-1"><strong>Aktif şehirler:</strong> ${topCities}</div>
                `;
            };

            const vectorMap = new window.jsVectorMap({
                selector: '#worldMap',
                map: 'world',
                markers: allMarkers,
                series: {
                    regions: [{
                        values: {},
                        scale: {},
                        normalizeFunction: 'polynomial',
                    }],
                },
                markerStyle: {
                    initial: { r: 5, fill: '#0d6efd', stroke: '#fff', strokeWidth: 1 },
                },
                onRegionTipShow: (_, tooltip, code) => {
                    const country = countryRows.find((c) => String(c.code || '').toUpperCase() === String(code).toUpperCase());
                    if (!country) return;
                    tooltip.html(`${country.flag_emoji || ''} ${country.name}<br>Oyuncu: ${Number(country.player_count || 0).toLocaleString('tr-TR')}`);
                },
                onRegionClick: (_, code) => {
                    renderCountryDetail(code);
                },
                onMarkerTipShow: (_, tooltip, index) => {
                    const marker = allMarkers[index];
                    if (!marker) return;
                    const typeText = marker.type === 'poi' ? 'POI' : 'Şehir';
                    tooltip.html(`${typeText}: ${marker.name}`);
                },
            });

            const applyLayer = (layerKey) => {
                const data = getLayerData(layerKey);
                vectorMap.series.regions[0].setValues(data.values);
                vectorMap.series.regions[0].setScale(data.scale);
                if (mapLegend) mapLegend.textContent = data.legend;
            };

            const applyMarkerFilter = (filter) => {
                if (filter === 'all') {
                    vectorMap.removeMarkers();
                    vectorMap.addMarkers(allMarkers);
                    return;
                }
                const filtered = allMarkers.filter((m) => m.type === filter);
                vectorMap.removeMarkers();
                vectorMap.addMarkers(filtered);
            };

            if (mapLayerSelect) {
                mapLayerSelect.addEventListener('change', (event) => {
                    applyLayer(event.target.value || 'none');
                });
            }
            if (markerFilterSelect) {
                markerFilterSelect.addEventListener('change', (event) => {
                    applyMarkerFilter(event.target.value || 'all');
                });
            }

            const defaultLayer = mapLayerSelect?.value || 'none';
            applyLayer(defaultLayer);
            applyMarkerFilter(markerFilterSelect?.value || 'all');
            renderCountryDetail(userCountryCode);
        } catch (error) {
            console.warn('Map library yüklenemedi:', error);
            renderMapFallback();
        }
    };

    if (cfg.toastMessage) showToast(cfg.toastMessage);
    refreshState().catch(() => null);
    setInterval(() => refreshState().catch(() => null), 15000);
    initMap().catch(() => null);
})();
