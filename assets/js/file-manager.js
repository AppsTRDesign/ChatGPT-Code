(function () {
    'use strict';

    const config = window.APP_CONFIG || {};
    const csrfToken = config.csrfToken;

    function showToast(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Başarılı' : 'Hata',
            text: message,
            confirmButtonColor: '#6941c6'
        });
    }

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return `${(bytes / Math.pow(1024, index)).toFixed(2)} ${units[index]}`;
    }

    function humanDate(dateString) {
        if (!dateString) {
            return '';
        }
        const date = new Date(dateString.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return dateString;
        }
        return date.toLocaleString('tr-TR');
    }

    async function fmRequest(action, payload = {}) {
        const response = await fetch(`${config.baseUrl}/api/files.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action, csrf_token: csrfToken, ...payload })
        });
        return response.json();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const app = document.querySelector('#fileManagerApp');
        if (!app) {
            return;
        }

        const grid = app.querySelector('[data-fm-grid]');
        const breadcrumbsEl = app.querySelector('[data-fm-breadcrumbs]');
        const usageStat = app.querySelector('[data-fm-stat="usage"]');
        const allowedEl = app.querySelector('[data-fm-allowed]');
        const contextMenu = document.querySelector('[data-fm-context]');
        const uploadZone = document.querySelector('#clientUploadZone');
        const toolbarButtons = app.querySelectorAll('[data-fm-action]');
        const folderTemplate = document.querySelector('#fm-folder-template');
        const fileTemplate = document.querySelector('#fm-file-template');

        const state = {
            folderId: null,
            selection: null,
            selectionType: null,
            folders: [],
            files: [],
            breadcrumbs: [],
            limits: {},
            settings: {},
            allFolders: [],
        };

        function clearSelection() {
            grid.querySelectorAll('.fm-item.is-active').forEach(el => el.classList.remove('is-active'));
            state.selection = null;
            state.selectionType = null;
        }

        function selectItem(element) {
            clearSelection();
            if (!element) {
                return;
            }
            element.classList.add('is-active');
            state.selection = element.dataset.id;
            state.selectionType = element.dataset.type;
        }

        function updateToolbar() {
            toolbarButtons.forEach(button => {
                const action = button.getAttribute('data-fm-action');
                const requiresSelection = ['rename', 'move', 'delete', 'zip'].includes(action);
                if (action === 'zip' && state.selectionType === 'file') {
                    button.disabled = true;
                } else if (requiresSelection) {
                    button.disabled = !state.selection;
                } else {
                    button.disabled = false;
                }
            });
        }

        function hideContextMenu() {
            if (contextMenu) {
                contextMenu.hidden = true;
            }
        }

        function showContextMenu(event, element) {
            if (!contextMenu) {
                return;
            }
            event.preventDefault();
            selectItem(element);
            updateToolbar();

            const type = element.dataset.type;
            const shareToken = element.dataset.shareToken || '';
            const isProtected = element.dataset.isProtected === '1';
            const isPublic = element.dataset.isPublic === '1';
            contextMenu.querySelectorAll('[data-action]').forEach(item => {
                const action = item.getAttribute('data-action');
                const folderOnly = ['protect', 'zip'];
                const fileOnly = ['share', 'visibility'];
                if (folderOnly.includes(action) && type !== 'folder') {
                    item.style.display = 'none';
                } else if (fileOnly.includes(action) && type !== 'file') {
                    item.style.display = 'none';
                } else if (action === 'share' || action === 'visibility') {
                    item.style.display = state.settings.public_sharing ? '' : 'none';
                } else if (action === 'protect') {
                    item.style.display = state.settings.folder_passwords ? '' : 'none';
                } else {
                    item.style.display = '';
                }

                if (action === 'share') {
                    item.textContent = shareToken ? 'Paylaşımı Kapat' : 'Paylaş';
                }
                if (action === 'visibility') {
                    item.textContent = isPublic ? 'Gizle' : 'Herkese Aç';
                }
                if (action === 'protect') {
                    item.textContent = isProtected ? 'Şifreyi Kaldır' : 'Şifrele';
                }
            });

            contextMenu.style.top = `${event.clientY}px`;
            contextMenu.style.left = `${event.clientX}px`;
            contextMenu.hidden = false;
        }

        function renderBreadcrumbs(items) {
            breadcrumbsEl.innerHTML = '';
            items.forEach((crumb, index) => {
                const span = document.createElement('span');
                span.className = 'breadcrumb-item';
                if (index === items.length - 1) {
                    span.classList.add('active');
                    span.textContent = crumb.name;
                } else {
                    const link = document.createElement('a');
                    link.href = '#';
                    link.textContent = crumb.name;
                    link.addEventListener('click', (event) => {
                        event.preventDefault();
                        loadFolder(crumb.id || null);
                    });
                    span.appendChild(link);
                }
                breadcrumbsEl.appendChild(span);
            });
        }

        function createItemElement(template, data, metaText) {
            const fragment = template.content.cloneNode(true);
            const el = fragment.querySelector('.fm-item');
            el.dataset.id = data.id;
            el.dataset.type = data.type;
            el.dataset.name = data.name;
            el.dataset.meta = metaText;
            if (typeof data.shareToken !== 'undefined') {
                el.dataset.shareToken = data.shareToken || '';
            }
            if (typeof data.isProtected !== 'undefined') {
                el.dataset.isProtected = data.isProtected ? '1' : '0';
            }
            if (typeof data.isPublic !== 'undefined') {
                el.dataset.isPublic = data.isPublic ? '1' : '0';
            }
            el.querySelector('.fm-name').textContent = data.name;
            el.querySelector('.fm-meta').textContent = metaText;
            if (data.type === 'folder') {
                if (data.isProtected) {
                    el.classList.add('is-protected');
                }
                if (data.isPublic) {
                    el.classList.add('is-public');
                }
            } else if (data.type === 'file') {
                if (data.isPublic) {
                    el.classList.add('is-public');
                }
            }
            return el;
        }

        function renderGrid() {
            grid.innerHTML = '';
            if (!state.folders.length && !state.files.length) {
                const empty = document.createElement('div');
                empty.className = 'text-white-50 small';
                empty.textContent = 'Bu klasörde içerik bulunmuyor.';
                grid.appendChild(empty);
                return;
            }

            state.folders.forEach(folder => {
                const meta = `${folder.file_count} öğe`;
                const element = createItemElement(folderTemplate, {
                    id: folder.id,
                    name: folder.name,
                    type: 'folder',
                    isProtected: folder.is_protected,
                    isPublic: folder.is_public,
                }, meta);
                grid.appendChild(element);
            });

            state.files.forEach(file => {
                const meta = `${formatBytes(file.size)} • ${humanDate(file.uploaded_at)}`;
                const element = createItemElement(fileTemplate, {
                    id: file.id,
                    name: file.filename,
                    type: 'file',
                    isPublic: !!file.is_public || !!file.share_token,
                    shareToken: file.share_token || '',
                }, meta);
                grid.appendChild(element);
            });
        }

        function updateStats() {
            if (usageStat) {
                usageStat.textContent = formatBytes(state.limits.storage_used);
            }
            if (allowedEl) {
                allowedEl.textContent = Array.isArray(state.limits.allowed_mime_types)
                    ? state.limits.allowed_mime_types.join(', ')
                    : '—';
            }
            if (uploadZone) {
                uploadZone.dispatchEvent(new CustomEvent('set-folder', {
                    detail: {
                        folderId: state.folderId,
                        parallelUploads: state.limits.max_concurrent_uploads,
                        acceptedFiles: Array.isArray(state.limits.allowed_mime_types) ? state.limits.allowed_mime_types : undefined,
                    }
                }));
            }
        }

        function bindGridEvents() {
            grid.querySelectorAll('.fm-item').forEach(item => {
                item.addEventListener('click', () => {
                    selectItem(item);
                    updateToolbar();
                });
                item.addEventListener('dblclick', () => {
                    if (item.dataset.type === 'folder') {
                        loadFolder(Number(item.dataset.id));
                    } else {
                        openFile(item.dataset.id, item.dataset.name);
                    }
                });
                item.addEventListener('contextmenu', (event) => {
                    showContextMenu(event, item);
                });
            });
        }

        function openFile(id, name) {
            const safeName = name.replace(/[^a-zA-Z0-9\-_\.]/g, '-');
            window.open(`${config.baseUrl}/file/${id}-${safeName}`, '_blank');
        }

        async function loadFolder(folderId = null) {
            hideContextMenu();
            clearSelection();
            updateToolbar();
            const response = await fmRequest('list', { folder_id: folderId });
            if (response.status !== 'success') {
                showToast('error', response.message || 'Klasörler yüklenemedi.');
                return;
            }
            state.folderId = response.folder?.id ?? null;
            state.folders = response.folders || [];
            state.files = response.files || [];
            state.breadcrumbs = response.breadcrumbs || [];
            state.limits = response.limits || {};
            const settings = response.settings || {};
            state.settings = {
                public_sharing: !!settings.public_sharing,
                folder_passwords: !!settings.folder_passwords,
                share_expiry_minutes: settings.share_expiry_minutes || 0,
            };
            state.allFolders = response.all_folders || [];

            renderBreadcrumbs(state.breadcrumbs);
            renderGrid();
            bindGridEvents();
            updateStats();
        }

        async function createFolder() {
            const allowPasswords = !!state.settings.folder_passwords;
            const html = `<input type="text" id="fm-folder-name" class="swal2-input" placeholder="Klasör adı">
                ${allowPasswords ? '<input type="password" id="fm-folder-pass" class="swal2-input" placeholder="Şifre (isteğe bağlı)">' : ''}`;
            const { value: formValues } = await Swal.fire({
                title: 'Yeni klasör',
                html,
                focusConfirm: false,
                preConfirm: () => {
                    const name = /** @type {HTMLInputElement} */(document.getElementById('fm-folder-name')).value.trim();
                    const passEl = document.getElementById('fm-folder-pass');
                    const password = passEl ? passEl.value : '';
                    if (!name) {
                        Swal.showValidationMessage('Lütfen bir isim girin.');
                        return null;
                    }
                    return { name, password };
                },
                confirmButtonText: 'Oluştur',
                cancelButtonText: 'Vazgeç',
                showCancelButton: true,
            });
            if (!formValues) {
                return;
            }
            const result = await fmRequest('create-folder', {
                name: formValues.name,
                parent_id: state.folderId,
                password: formValues.password,
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder(state.folderId);
            } else {
                showToast('error', result.message || 'Klasör oluşturulamadı.');
            }
        }

        async function renameSelected() {
            if (!state.selection) {
                return;
            }
            const currentName = state.selectionType === 'folder'
                ? state.folders.find(f => String(f.id) === state.selection)?.name
                : state.files.find(f => String(f.id) === state.selection)?.filename;
            const { value } = await Swal.fire({
                title: 'Adı düzenle',
                input: 'text',
                inputValue: currentName || '',
                showCancelButton: true,
                confirmButtonText: 'Kaydet',
                cancelButtonText: 'İptal',
            });
            if (!value) {
                return;
            }
            const action = state.selectionType === 'folder' ? 'rename-folder' : 'rename-file';
            const payload = state.selectionType === 'folder'
                ? { folder_id: Number(state.selection), name: value }
                : { file_id: Number(state.selection), filename: value };
            const result = await fmRequest(action, payload);
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder(state.folderId);
            } else {
                showToast('error', result.message || 'Güncelleme başarısız.');
            }
        }

        async function deleteSelected() {
            if (!state.selection) {
                return;
            }
            const confirm = await Swal.fire({
                icon: 'warning',
                title: 'Silmek istiyor musunuz?',
                text: 'Bu işlem geri alınamaz.',
                showCancelButton: true,
                confirmButtonText: 'Evet, sil',
                cancelButtonText: 'İptal'
            });
            if (!confirm.isConfirmed) {
                return;
            }
            const action = state.selectionType === 'folder' ? 'delete-folder' : 'delete-file';
            const key = state.selectionType === 'folder' ? 'folder_id' : 'file_id';
            const result = await fmRequest(action, { [key]: Number(state.selection) });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder(state.folderId);
            } else {
                showToast('error', result.message || 'Silme işlemi başarısız.');
            }
        }

        async function moveSelected() {
            if (!state.selection) {
                return;
            }
            const options = state.allFolders
                .filter(folder => String(folder.id) !== state.selection)
                .map(folder => `<option value="${folder.id}">${folder.path}</option>`)
                .join('');
            const html = `<select id="fm-move-target" class="swal2-select">
                <option value="">Ana Depo</option>${options}
            </select>`;
            const { value } = await Swal.fire({
                title: 'Hedef klasör',
                html,
                focusConfirm: false,
                preConfirm: () => {
                    const select = /** @type {HTMLSelectElement} */(document.getElementById('fm-move-target'));
                    return select.value === '' ? null : Number(select.value);
                },
                showCancelButton: true,
                confirmButtonText: 'Taşı',
                cancelButtonText: 'İptal',
            });
            if (value === undefined) {
                return;
            }
            const action = state.selectionType === 'folder' ? 'move-folder' : 'move-file';
            const payloadKey = state.selectionType === 'folder' ? 'folder_id' : 'file_id';
            const result = await fmRequest(action, {
                [payloadKey]: Number(state.selection),
                target_id: value || null,
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder(state.folderId);
            } else {
                showToast('error', result.message || 'Taşıma başarısız.');
            }
        }

        async function shareSelected() {
            if (state.selectionType !== 'file') {
                return;
            }
            if (!state.settings.public_sharing) {
                showToast('error', 'Paylaşım özelliği devre dışı.');
                return;
            }
            const result = await fmRequest('share-file', { file_id: Number(state.selection) });
            if (result.status === 'success' && result.share) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Paylaşım bağlantısı',
                    html: `<div class="text-start"><p class="mb-1">Bağlantıyı kopyalayın:</p>
                        <code class="d-block p-2 bg-dark rounded">${result.share.url}</code>
                        <p class="small text-white-50 mb-0">Süre: ${state.settings.share_expiry_minutes || 60} dk</p></div>`,
                    confirmButtonText: 'Tamam'
                });
            } else {
                showToast('error', result.message || 'Paylaşım oluşturulamadı.');
            }
        }

        async function toggleVisibility() {
            if (state.selectionType !== 'file') {
                return;
            }
            const file = state.files.find(f => String(f.id) === state.selection);
            const nextValue = file?.is_public ? 0 : 1;
            const result = await fmRequest('toggle-file-visibility', {
                file_id: Number(state.selection),
                is_public: nextValue,
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder(state.folderId);
            } else {
                showToast('error', result.message || 'Güncelleme başarısız.');
            }
        }

        async function protectFolder() {
            if (state.selectionType !== 'folder') {
                return;
            }
            if (!state.settings.folder_passwords) {
                showToast('error', 'Klasör şifreleme devre dışı.');
                return;
            }
            const { value } = await Swal.fire({
                title: 'Klasör Şifresi',
                input: 'password',
                inputLabel: 'Şifre belirleyin (boş bırakmak kaldırır)',
                showCancelButton: true,
                confirmButtonText: 'Kaydet',
                cancelButtonText: 'İptal'
            });
            if (value === undefined) {
                return;
            }
            const result = await fmRequest('set-folder-password', {
                folder_id: Number(state.selection),
                password: value || '',
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder(state.folderId);
            } else {
                showToast('error', result.message || 'Şifre güncellenemedi.');
            }
        }

        async function zipFolder() {
            if (state.selectionType !== 'folder') {
                return;
            }
            const result = await fmRequest('zip-folder', { folder_id: Number(state.selection) });
            if (result.status === 'success' && result.archive) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Arşiv hazır',
                    html: `<a class="btn btn-gradient" href="${result.archive.download_url}">Zip&#39;i indir</a>`,
                    confirmButtonText: 'Tamam'
                });
            } else {
                showToast('error', result.message || 'Arşiv oluşturulamadı.');
            }
        }

        toolbarButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const action = button.getAttribute('data-fm-action');
                hideContextMenu();
                switch (action) {
                    case 'new-folder':
                        await createFolder();
                        break;
                    case 'upload':
                        if (uploadZone) {
                            uploadZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        break;
                    case 'rename':
                        await renameSelected();
                        break;
                    case 'move':
                        await moveSelected();
                        break;
                    case 'zip':
                        await zipFolder();
                        break;
                    case 'delete':
                        await deleteSelected();
                        break;
                    default:
                        break;
                }
            });
        });

        if (contextMenu) {
            contextMenu.addEventListener('click', async (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }
                const action = target.getAttribute('data-action');
                const selectedEl = grid.querySelector('.fm-item.is-active');
                const selectedName = selectedEl ? selectedEl.dataset.name : '';
                const currentShareToken = selectedEl ? selectedEl.dataset.shareToken : '';
                hideContextMenu();
                switch (action) {
                    case 'open':
                        if (state.selectionType === 'folder') {
                            loadFolder(Number(state.selection));
                        } else if (state.selectionType === 'file') {
                            openFile(state.selection, selectedName);
                        }
                        break;
                    case 'rename':
                        await renameSelected();
                        break;
                    case 'move':
                        await moveSelected();
                        break;
                    case 'share':
                        if (currentShareToken) {
                            const result = await fmRequest('revoke-share', { file_id: Number(state.selection) });
                            if (result.status === 'success') {
                                showToast('success', result.message);
                                await loadFolder(state.folderId);
                            } else {
                                showToast('error', result.message || 'Paylaşım kapatılamadı.');
                            }
                        } else {
                            await shareSelected();
                        }
                        break;
                    case 'protect':
                        await protectFolder();
                        break;
                    case 'visibility':
                        await toggleVisibility();
                        break;
                    case 'zip':
                        await zipFolder();
                        break;
                    case 'delete':
                        await deleteSelected();
                        break;
                    default:
                        break;
                }
            });
        }

        document.addEventListener('click', hideContextMenu);
        document.addEventListener('scroll', hideContextMenu);

        document.addEventListener('upload:completed', () => {
            loadFolder(state.folderId);
        });

        loadFolder(app.dataset.initialFolder ? Number(app.dataset.initialFolder) : null);
    });
})();
