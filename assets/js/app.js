(function () {
    'use strict';

    const cfg = window.APP_CONFIG || {};
    const bootstrapData = window.GAME_BOOTSTRAP || {};
    const csrf = cfg.csrf || '';
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
    };

    const refreshState = async () => {
        const res = await fetch('/api/state', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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
            return post('/api/action/work', { resource });
        }
        if (action === 'battle') {
            return post('/api/action/battle', {});
        }
        if (action === 'upgrade') {
            return post('/api/action/upgrade', { stat: button.dataset.stat || 'strength' });
        }
        if (action === 'market-create') {
            return post('/api/market/create', {
                resource_id: document.getElementById('offerResourceId')?.value || 0,
                quantity: document.getElementById('offerQty')?.value || 0,
                price_per_unit: document.getElementById('offerPrice')?.value || 0,
            });
        }
        if (action === 'market-buy') {
            return post('/api/market/buy', { offer_id: button.dataset.offerId || 0 });
        }
        if (action === 'factory-create') {
            return post('/api/factory/create', { factory_type_id: document.getElementById('factoryTypeId')?.value || 0 });
        }
        if (action === 'factory-produce') {
            return post('/api/factory/produce', { factory_id: button.dataset.factoryId || 0 });
        }
        if (action === 'war-start') {
            return post('/api/war/start', { defender_country_id: document.getElementById('defenderCountryId')?.value || 0 });
        }
        if (action === 'war-attack') {
            return post('/api/war/attack', { war_id: button.dataset.warId || 0 });
        }
        if (action === 'party-create') {
            return post('/api/party/create', {
                name: document.getElementById('partyName')?.value || '',
                ideology: document.getElementById('partyIdeology')?.value || '',
            });
        }
        if (action === 'party-join') {
            return post('/api/party/join', { party_id: button.dataset.partyId || 0 });
        }
        if (action === 'party-leave') {
            return post('/api/party/leave', {});
        }
        if (action === 'election-open') {
            return post('/api/election/open', {});
        }
        if (action === 'election-vote') {
            return post('/api/election/vote', {
                election_id: button.dataset.electionId || 0,
                party_id: document.getElementById('electionPartyId')?.value || 0,
            });
        }
        if (action === 'law-propose') {
            return post('/api/law/propose', {
                title: document.getElementById('lawTitle')?.value || '',
                body: document.getElementById('lawBody')?.value || '',
            });
        }
        if (action === 'law-vote') {
            return post('/api/law/vote', {
                law_id: button.dataset.lawId || 0,
                vote: button.dataset.vote || '',
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

    const initMap = async () => {
        const mapElement = document.getElementById('worldMap');
        if (!mapElement) return;

        try {
            if (typeof window.jsVectorMap === 'undefined') {
                await loadScript('https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/js/jsvectormap.min.js');
                await loadScript('https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/maps/world.js');
            }

            if (typeof window.jsVectorMap === 'undefined') {
                return;
            }

            const markers = (bootstrapData.map?.cities || []).map((city) => ({
                name: `${city.country_name} / ${city.name} (${city.player_count})`,
                coords: [Number(city.lat), Number(city.lng)],
            }));

            new window.jsVectorMap({
                selector: '#worldMap',
                map: 'world',
                markers,
                markerStyle: {
                    initial: { r: 5, fill: '#0d6efd', stroke: '#fff', strokeWidth: 1 },
                },
            });
        } catch (error) {
            console.warn('Map library yüklenemedi:', error);
        }
    };

    if (cfg.toastMessage) showToast(cfg.toastMessage);
    refreshState().catch(() => null);
    setInterval(() => refreshState().catch(() => null), 15000);
    initMap().catch(() => null);
})();
