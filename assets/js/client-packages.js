(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const container = document.getElementById('clientPackages');
    if (!container) {
        return;
    }

    const providerModalEl = document.getElementById('paymentProviderModal');
    const providerModal = providerModalEl && window.bootstrap ? new window.bootstrap.Modal(providerModalEl) : null;
    const providerList = document.getElementById('paymentProviderList');
    const bankModalEl = document.getElementById('bankTransferModal');
    const bankModal = bankModalEl && window.bootstrap ? new window.bootstrap.Modal(bankModalEl) : null;
    const bankInstructionsEl = document.getElementById('bankInstructions');
    const bankPackageEl = document.querySelector('[data-bank-package]');
    const bankAmountEl = document.querySelector('[data-bank-amount]');
    const bankForm = document.getElementById('bankTransferForm');
    const bankNoteField = bankForm ? bankForm.querySelector('[name="note"]') : null;

    let bankDropzone = null;
    if (bankForm && bankForm.dropzone) {
        bankDropzone = bankForm.dropzone;
    } else if (bankForm && window.Dropzone && typeof window.Dropzone.forElement === 'function') {
        try {
            bankDropzone = window.Dropzone.forElement(bankForm);
        } catch (error) {
            console.warn('Dropzone erişimi başarısız:', error);
        }
    }

    let packagesCache = [];
    let paymentProviders = {};
    let bankInstructions = appConfig.bankInstructions || '';
    let currency = 'TRY';
    let activePackage = null;
    let activePackageId = null;

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
    };

    const renderPackages = (packages) => {
        container.innerHTML = '';
        if (!packages.length) {
            container.innerHTML = '<div class="col-12 text-center text-white-50">Aktif paket bulunamadı.</div>';
            return;
        }
        packagesCache = packages;
        packages.forEach(pkg => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            const isActive = activePackageId !== null && Number(activePackageId) === Number(pkg.id);
            const maxUploadText = pkg.max_upload_size ? formatBytes(Number(pkg.max_upload_size)) : 'Sınırsız';
            const storageText = pkg.storage_limit ? formatBytes(Number(pkg.storage_limit)) : '0 B';
            col.innerHTML = `
                <div class="card card-glass h-100 p-4 text-center${isActive ? ' card-package-active' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h3 class="h4 text-white mb-0">${pkg.name}</h3>
                        ${isActive ? '<span class="badge bg-success-subtle text-success fw-semibold">Aktif</span>' : ''}
                    </div>
                    <p class="display-6 fw-bold text-white">${pkg.price > 0 ? pkg.price.toFixed(2) + ' ₺' : 'Ücretsiz'}</p>
                    <span class="badge badge-custom mb-2">Depo: ${storageText}</span>
                    <span class="badge bg-transparent border border-light-subtle text-white-50 mb-3">Tek dosya: ${maxUploadText}</span>
                    <p class="text-white-50 small mb-3">Aynı anda ${pkg.max_concurrent_uploads} yükleme hakkı</p>
                    <ul class="list-unstyled text-white-50 mb-4">
                        ${(pkg.features || []).map(feature => `<li>• ${feature}</li>`).join('') || '<li>• Standart özellikler</li>'}
                    </ul>
                    <button class="btn btn-gradient w-100" data-action="purchase" data-id="${pkg.id}" ${isActive ? 'disabled' : ''}>${isActive ? 'Kullanımda' : 'Paketi Seç'}</button>
                </div>
            `;
            container.appendChild(col);
        });
    };

    const loadPackages = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-packages', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paketler alınamadı');
        }
        paymentProviders = data.payment_providers || {};
        bankInstructions = data.bank_instructions || '';
        currency = data.currency || 'TRY';
        activePackageId = data.active_package_id ?? null;
        renderPackages(data.data || []);
    };

    const refreshBankModal = () => {
        if (!bankModalEl || !activePackage) {
            return;
        }
        if (bankPackageEl) {
            bankPackageEl.textContent = `${activePackage.name}`;
        }
        if (bankAmountEl) {
            const amountText = activePackage.price > 0 ? `${activePackage.price.toFixed(2)} ${currency}` : 'Ücretsiz';
            bankAmountEl.textContent = amountText;
        }
        if (bankInstructionsEl) {
            bankInstructionsEl.innerHTML = bankInstructions ? bankInstructions : '<em>Yönetici henüz ödeme talimatı eklememiş.</em>';
        }
        if (bankNoteField) {
            bankNoteField.value = '';
        }
        if (bankForm) {
            bankForm.dataset.transactionId = bankForm.querySelector('input[name="transaction_id"]').value || '';
        }
        if (bankDropzone) {
            bankDropzone.removeAllFiles(true);
        }
    };

    const handleProviderSelection = async (providerKey) => {
        if (!activePackage) {
            return;
        }
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'purchase-package', package_id: activePackage.id, provider: providerKey, csrf_token: appConfig.csrfToken })
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Paket seçilemedi');
            }
            if (providerKey === 'bank_transfer') {
                if (!data.transaction_id) {
                    throw new Error('İşlem kimliği alınamadı.');
                }
                if (bankForm) {
                    bankForm.dataset.transactionId = String(data.transaction_id);
                    const hiddenId = bankForm.querySelector('input[name="transaction_id"]');
                    if (hiddenId) {
                        hiddenId.value = String(data.transaction_id);
                    }
                }
                refreshBankModal();
                providerModal?.hide();
                bankModal?.show();
            } else if (data.payment_url) {
                window.location.href = data.payment_url;
            } else {
                Swal.fire({ icon: 'success', title: 'Başarılı', text: data.message || 'Paketiniz işleme alındı.' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        }
    };

    container.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action="purchase"]');
        if (!button) {
            return;
        }
        const id = Number(button.dataset.id);
        activePackage = packagesCache.find(item => Number(item.id) === id) || null;
        if (!activePackage) {
            Swal.fire({ icon: 'error', title: 'Hata', text: 'Paket bulunamadı.' });
            return;
        }
        const available = Object.entries(paymentProviders).filter(([, enabled]) => Boolean(enabled));
        if (!available.length) {
            Swal.fire({ icon: 'warning', title: 'Ödeme yöntemi kapalı', text: 'Şu anda herhangi bir ödeme yöntemi aktif değil.' });
            return;
        }
        if (available.length === 1) {
            handleProviderSelection(available[0][0]);
            return;
        }
        if (providerList) {
            providerList.innerHTML = '';
            available.forEach(([key]) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                let label = '';
                switch (key) {
                    case 'iyzico':
                        label = 'Iyzico (Kredi/Banka Kartı)';
                        break;
                    case 'stripe':
                        label = 'Stripe (Kart)';
                        break;
                    case 'bank_transfer':
                    default:
                        label = 'Havale / EFT';
                        break;
                }
                item.innerHTML = `<span>${label}</span><i class="bi bi-chevron-right"></i>`;
                item.addEventListener('click', () => handleProviderSelection(key));
                providerList.appendChild(item);
            });
        }
        providerModal?.show();
    });

    if (bankDropzone && bankModalEl) {
        bankModalEl.addEventListener('hidden.bs.modal', () => {
            if (bankDropzone) {
                bankDropzone.removeAllFiles(true);
            }
            if (bankNoteField) {
                bankNoteField.value = '';
            }
            if (bankForm) {
                bankForm.dataset.transactionId = '';
                const hiddenId = bankForm.querySelector('input[name="transaction_id"]');
                if (hiddenId) {
                    hiddenId.value = '';
                }
            }
        });
    }

    document.addEventListener('upload:completed', (event) => {
        if (!bankForm || !event.detail) {
            return;
        }
        if (bankForm.dataset.transactionId && bankDropzone && event.detail.transaction_id) {
            Swal.fire({ icon: 'success', title: 'Dekont alındı', text: 'Ödeme bildiriminiz incelenmek üzere kaydedildi.' });
            bankModal?.hide();
        }
    });

    loadPackages().catch(error => {
        console.error(error);
        container.innerHTML = '<div class="col-12 text-center text-danger">Paketler yüklenemedi.</div>';
    });
})();
