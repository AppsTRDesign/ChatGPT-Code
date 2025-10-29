(function () {
    'use strict';

    const config = window.APP_CONFIG || {};
    const csrfToken = config.csrfToken || '';

    const FILE_ICON_MAP = [
        { match: (type) => type.startsWith('image/'), icon: 'bi-file-earmark-image' },
        { match: (type) => type.startsWith('video/'), icon: 'bi-file-earmark-play' },
        { match: (type) => type.startsWith('audio/'), icon: 'bi-file-earmark-music' },
        { match: (type) => type === 'application/pdf', icon: 'bi-file-earmark-pdf' },
        { match: (type) => type.includes('zip') || type.includes('compressed'), icon: 'bi-file-earmark-zip' },
        { match: (type) => type.includes('word') || type.includes('msword'), icon: 'bi-file-earmark-word' },
        { match: (type) => type.includes('sheet') || type.includes('excel'), icon: 'bi-file-earmark-spreadsheet' },
        { match: (type) => type.startsWith('text/'), icon: 'bi-file-earmark-text' },
    ];

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

    function humanDate(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString('tr-TR');
    }

    function iconForFile(type) {
        if (!type || typeof type !== 'string') {
            return 'bi-file-earmark';
        }
        const match = FILE_ICON_MAP.find(rule => rule.match(type));
        return match ? match.icon : 'bi-file-earmark';
    }

    function isFormElement(element) {
        return element && (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.isContentEditable);
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
        const uploadLimitEl = app.querySelector('[data-fm-upload-limit]');
        const contextMenu = document.querySelector('[data-fm-context]');
        const uploadZone = document.querySelector('#clientUploadZone');
        const toolbarButtons = Array.from(app.querySelectorAll('[data-fm-action]'));
        const folderTemplate = document.querySelector('#fm-folder-template');
        const fileTemplate = document.querySelector('#fm-file-template');
        const paginationWrap = app.querySelector('[data-fm-pagination]');
        const summaryEl = paginationWrap ? paginationWrap.querySelector('[data-fm-summary]') : null;
        const pageLabel = paginationWrap ? paginationWrap.querySelector('[data-fm-page-label]') : null;
        const pageButtons = paginationWrap ? Array.from(paginationWrap.querySelectorAll('[data-fm-page]')) : [];
        const sortSelect = app.querySelector('[data-fm-sort]');

        const state = {
            folderId: null,
            folders: [],
            files: [],
            breadcrumbs: [],
            limits: {},
            settings: {},
            allFolders: [],
            selection: {
                files: new Set(),
                folders: new Set(),
            },
            pagination: {
                page: 1,
                totalPages: 1,
                total: 0,
                perPage: 24,
            },
            sort: {
                key: 'name',
                direction: 'asc',
            },
            focused: null,
        };

        function getSelectionCount() {
            return state.selection.files.size + state.selection.folders.size;
        }

        function clearSelection() {
            grid.querySelectorAll('.fm-item.is-active').forEach(el => el.classList.remove('is-active'));
            state.selection.files.clear();
            state.selection.folders.clear();
            state.focused = null;
        }

        function addSelection(element) {
            if (!element) {
                return;
            }
            const type = element.dataset.type;
            const id = Number(element.dataset.id);
            if (Number.isNaN(id)) {
                return;
            }
            if (type === 'folder') {
                state.selection.folders.add(id);
            } else {
                state.selection.files.add(id);
            }
            element.classList.add('is-active');
            state.focused = { type, id };
        }

        function removeSelection(element) {
            if (!element) {
                return;
            }
            const type = element.dataset.type;
            const id = Number(element.dataset.id);
            if (Number.isNaN(id)) {
                return;
            }
            if (type === 'folder') {
                state.selection.folders.delete(id);
            } else {
                state.selection.files.delete(id);
            }
            element.classList.remove('is-active');
            if (state.focused && state.focused.id === id && state.focused.type === type) {
                state.focused = null;
            }
        }

        function selectSingle(element) {
            clearSelection();
            addSelection(element);
        }

        function toggleSelection(element, allowToggle) {
            if (!element) {
                return;
            }
            if (!allowToggle) {
                selectSingle(element);
                return;
            }
            if (element.classList.contains('is-active')) {
                removeSelection(element);
            } else {
                addSelection(element);
            }
        }

        function getSingleSelection() {
            if (getSelectionCount() !== 1) {
                return null;
            }
            if (state.selection.files.size === 1) {
                const id = Array.from(state.selection.files)[0];
                return { type: 'file', id };
            }
            if (state.selection.folders.size === 1) {
                const id = Array.from(state.selection.folders)[0];
                return { type: 'folder', id };
            }
            return null;
        }

        function firstSelectedElement() {
            const selected = grid.querySelector('.fm-item.is-active');
            return selected || null;
        }

        function updateToolbar() {
            const selectedFiles = state.selection.files.size;
            const selectedFolders = state.selection.folders.size;
            const totalSelected = selectedFiles + selectedFolders;
            toolbarButtons.forEach(button => {
                const action = button.getAttribute('data-fm-action');
                switch (action) {
                    case 'rename':
                    case 'move':
                        button.disabled = action === 'rename' ? totalSelected !== 1 : totalSelected === 0;
                        break;
                    case 'zip':
                        button.disabled = totalSelected === 0;
                        break;
                    case 'delete':
                        button.disabled = totalSelected === 0;
                        break;
                    case 'select-all':
                        button.disabled = state.files.length === 0 && state.folders.length === 0;
                        break;
                    default:
                        button.disabled = false;
                        break;
                }
            });
        }

        function hideContextMenu() {
            if (contextMenu && !contextMenu.hidden) {
                contextMenu.hidden = true;
            }
        }

        function showContextMenu(event, element) {
            if (!contextMenu) {
                return;
            }
            event.preventDefault();
            const multiKey = event.ctrlKey || event.metaKey;
            if (!element.classList.contains('is-active')) {
                toggleSelection(element, multiKey);
                updateToolbar();
            }
            const selectedCount = getSelectionCount();
            const selectedFiles = state.selection.files.size;
            const selectedFolders = state.selection.folders.size;
            const type = element.dataset.type;
            contextMenu.dataset.targetId = element.dataset.id;
            contextMenu.dataset.targetType = type;
            contextMenu.dataset.shareToken = element.dataset.shareToken || '';
            contextMenu.dataset.isProtected = element.dataset.isProtected || '0';

            contextMenu.querySelectorAll('[data-action]').forEach(item => {
                const action = item.getAttribute('data-action');
                item.style.display = '';

                if (selectedCount > 1) {
                    if (action === 'delete' || action === 'zip' || action === 'move') {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                    return;
                }

                if (type === 'folder') {
                    if (action === 'share') {
                        item.style.display = 'none';
                        return;
                    }
                    if (action === 'protect') {
                        item.textContent = element.dataset.isProtected === '1' ? 'Şifreyi Kaldır' : 'Şifrele';
                        item.style.display = state.settings.folder_passwords ? '' : 'none';
                        return;
                    }
                } else if (type === 'file') {
                    if (action === 'protect') {
                        item.style.display = 'none';
                        return;
                    }
                    if (action === 'share') {
                        item.style.display = state.settings.public_sharing ? '' : 'none';
                        item.textContent = 'Paylaş';
                        return;
                    }
                }
                if (action === 'zip') {
                    item.style.display = '';
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
                        loadFolder({ folderId: crumb.id || null, resetPage: true });
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
            el.querySelector('.fm-name').textContent = data.name;
            el.querySelector('.fm-meta').textContent = metaText;
            const iconEl = el.querySelector('.fm-icon');
            if (data.type === 'folder') {
                iconEl.innerHTML = '<i class="bi bi-folder2"></i>';
                if (data.isProtected) {
                    el.classList.add('is-protected');
                }
            } else {
                iconEl.innerHTML = `<i class="bi ${iconForFile(data.mime || '')}"></i>`;
                if (data.isShared) {
                    el.classList.add('is-shared');
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
                }, meta);
                grid.appendChild(element);
            });

            state.files.forEach(file => {
                const meta = `${formatBytes(file.size)} • ${humanDate(file.uploaded_at)}`;
                const element = createItemElement(fileTemplate, {
                    id: file.id,
                    name: file.filename,
                    type: 'file',
                    mime: file.type,
                    isShared: Boolean(file.share_token),
                    shareToken: file.share_token || '',
                }, meta);
                grid.appendChild(element);
            });
        }

        function updatePagination() {
            if (!paginationWrap) {
                return;
            }
            const { page, totalPages, total } = state.pagination;
            if (pageLabel) {
                pageLabel.textContent = `${page} / ${totalPages}`;
            }
            if (summaryEl) {
                const totalFiles = state.limits.total_files ?? total;
                summaryEl.textContent = `${totalFiles} dosya • Sayfa ${page}/${totalPages}`;
            }
            pageButtons.forEach(button => {
                const direction = button.getAttribute('data-fm-page');
                if (direction === 'prev') {
                    button.disabled = page <= 1;
                } else if (direction === 'next') {
                    button.disabled = page >= totalPages;
                }
            });
        }

        function updateStats() {
            if (usageStat) {
                const used = state.limits.storage_used ?? 0;
                const total = state.limits.storage_total ?? null;
                usageStat.textContent = total ? `${formatBytes(used)} / ${formatBytes(total)}` : formatBytes(used);
            }
            if (allowedEl) {
                const allowedList = Array.isArray(state.limits.allowed_extensions) ? state.limits.allowed_extensions : [];
                allowedEl.textContent = allowedList.length ? allowedList.map(ext => `.${ext}`).join(', ') : '—';
            }
            if (uploadZone) {
                const allowedList = Array.isArray(state.limits.allowed_extensions) ? state.limits.allowed_extensions : [];
                uploadZone.dataset.accepted = allowedList.map(ext => `.${ext}`).join(',');
                const maxFiles = state.limits.max_concurrent_uploads ?? '';
                uploadZone.dataset.maxFiles = maxFiles || '';
                const maxUploadBytes = state.limits.max_upload_size ?? null;
                uploadZone.dataset.maxFilesize = maxUploadBytes ? (maxUploadBytes / 1048576).toFixed(2) : '';
                uploadZone.dispatchEvent(new CustomEvent('set-folder', {
                    detail: {
                        folderId: state.folderId,
                        parallelUploads: state.limits.max_concurrent_uploads,
                        acceptedFiles: allowedList.map(ext => `.${ext}`),
                        maxFiles,
                        maxFilesizeMb: maxUploadBytes ? maxUploadBytes / 1048576 : null,
                    }
                }));
            }
            if (uploadLimitEl) {
                const limit = state.limits.max_concurrent_uploads ?? 1;
                const packageName = state.limits.package_name ? ` • Paket: ${state.limits.package_name}` : '';
                const maxUploadBytes = state.limits.max_upload_size ?? null;
                const perFileText = maxUploadBytes ? ` • Tek dosya limiti: ${formatBytes(maxUploadBytes)}` : '';
                uploadLimitEl.textContent = `Eş zamanlı yükleme limiti: ${limit}${perFileText}${packageName}`;
            }
        }

        function openFile(id, name) {
            const safeName = name.replace(/[^a-zA-Z0-9\-_.]/g, '-');
            window.open(`${config.baseUrl}/file/${id}-${safeName}`, '_blank');
        }

        async function loadFolder(options = {}) {
            hideContextMenu();
            const requestOptions = { ...options };
            let targetFolderId = state.folderId;
            if (Object.prototype.hasOwnProperty.call(requestOptions, 'folderId')) {
                targetFolderId = requestOptions.folderId;
            }
            if (requestOptions.resetPage) {
                state.pagination.page = 1;
            }
            if (Object.prototype.hasOwnProperty.call(requestOptions, 'page')) {
                state.pagination.page = Math.max(1, parseInt(requestOptions.page, 10) || 1);
            }

            clearSelection();
            updateToolbar();

            const response = await fmRequest('list', {
                folder_id: targetFolderId,
                page: state.pagination.page,
                per_page: state.pagination.perPage,
                sort: state.sort.key,
                direction: state.sort.direction,
            });

            if (response.status !== 'success') {
                showToast('error', response.message || 'Klasörler yüklenemedi.');
                return;
            }

            state.folderId = response.folder?.id ?? null;
            state.folders = response.folders || [];
            state.files = response.files || [];
            state.breadcrumbs = response.breadcrumbs || [];
            state.limits = response.limits || {};
            state.settings = {
                public_sharing: Boolean(response.settings?.public_sharing),
                folder_passwords: Boolean(response.settings?.folder_passwords),
                share_expiry_minutes: response.settings?.share_expiry_minutes || 0,
            };
            state.allFolders = response.all_folders || [];
            state.pagination = {
                page: response.pagination?.page ?? state.pagination.page,
                totalPages: response.pagination?.total_pages ?? 1,
                total: response.pagination?.total ?? state.files.length,
                perPage: response.pagination?.per_page ?? state.pagination.perPage,
            };
            state.sort = {
                key: response.pagination?.sort ?? state.sort.key,
                direction: response.pagination?.direction ?? state.sort.direction,
            };

            renderBreadcrumbs(state.breadcrumbs);
            renderGrid();
            updateToolbar();
            updateStats();
            updatePagination();

            if (sortSelect) {
                const optionValue = `${state.sort.key}|${state.sort.direction}`;
                if (sortSelect.value !== optionValue) {
                    sortSelect.value = optionValue;
                }
            }
        }

        async function createFolder() {
            const html = '<input type="text" id="fm-folder-name" class="swal2-input" placeholder="Klasör adı">';
            const { value: formValues } = await Swal.fire({
                title: 'Yeni klasör',
                html,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Oluştur',
                cancelButtonText: 'Vazgeç',
                preConfirm: () => {
                    const nameEl = document.getElementById('fm-folder-name');
                    const name = nameEl ? nameEl.value.trim() : '';
                    if (!name) {
                        Swal.showValidationMessage('Lütfen klasör adı girin.');
                        return null;
                    }
                    return { name };
                }
            });
            if (!formValues) {
                return;
            }
            const result = await fmRequest('create-folder', {
                name: formValues.name,
                parent_id: state.folderId,
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Klasör oluşturulamadı.');
            }
        }

        async function createTextFile() {
            const allowedList = Array.isArray(state.limits.allowed_extensions) ? state.limits.allowed_extensions : [];
            const suggestedExt = allowedList.length ? allowedList[0] : 'txt';
            const maxUploadBytes = state.limits.max_upload_size ? Number(state.limits.max_upload_size) : null;
            const html = `
                <input type="text" id="fm-file-name" class="swal2-input" value="yeni-dosya.${suggestedExt}" placeholder="Dosya adı">
                <textarea id="fm-file-content" class="swal2-textarea" placeholder="Dosya içeriği" rows="4"></textarea>
            `;
            const { value } = await Swal.fire({
                title: 'Yeni dosya oluştur',
                html,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Oluştur',
                cancelButtonText: 'Vazgeç',
                preConfirm: () => {
                    const nameInput = document.getElementById('fm-file-name');
                    const contentInput = document.getElementById('fm-file-content');
                    const name = nameInput ? nameInput.value.trim() : '';
                    const content = contentInput ? contentInput.value : '';
                    if (!name) {
                        Swal.showValidationMessage('Lütfen dosya adı girin.');
                        return null;
                    }
                    const extension = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
                    if (!extension) {
                        Swal.showValidationMessage('Dosya uzantısı belirtilmelidir.');
                        return null;
                    }
                    if (allowedList.length && !allowedList.includes(extension)) {
                        Swal.showValidationMessage(`Desteklenmeyen uzantı (.${extension}).`);
                        return null;
                    }
                    const contentBytes = new TextEncoder().encode(content).length;
                    if (maxUploadBytes && contentBytes > maxUploadBytes) {
                        Swal.showValidationMessage(`Dosya içeriği çok büyük. Maksimum ${formatBytes(maxUploadBytes)} olabilir.`);
                        return null;
                    }
                    return { name, content };
                }
            });
            if (!value) {
                return;
            }
            const result = await fmRequest('create-text-file', {
                name: value.name,
                content: value.content,
                folder_id: state.folderId,
            });
            if (result.status === 'success') {
                showToast('success', result.message || 'Dosya oluşturuldu.');
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Dosya oluşturulamadı.');
            }
        }

        async function renameSelected() {
            const selection = getSingleSelection();
            if (!selection) {
                return;
            }
            const source = selection.type === 'folder'
                ? state.folders.find(f => f.id === selection.id)
                : state.files.find(f => f.id === selection.id);
            const currentName = selection.type === 'folder' ? source?.name : source?.filename;
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
            const action = selection.type === 'folder' ? 'rename-folder' : 'rename-file';
            const payload = selection.type === 'folder'
                ? { folder_id: selection.id, name: value }
                : { file_id: selection.id, filename: value };
            const result = await fmRequest(action, payload);
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Güncelleme başarısız.');
            }
        }

        async function moveSelected() {
            const selectedFiles = Array.from(state.selection.files);
            const selectedFolders = Array.from(state.selection.folders);
            const totalSelected = selectedFiles.length + selectedFolders.length;
            if (totalSelected === 0) {
                return;
            }
            const blockedFolders = new Set(selectedFolders.map(id => String(id)));
            const options = state.allFolders
                .filter(folder => {
                    if (blockedFolders.has(String(folder.id))) {
                        return false;
                    }
                    const ancestors = folder.ancestors || [];
                    return !ancestors.some(ancestorId => blockedFolders.has(String(ancestorId)));
                })
                .map(folder => `<option value="${folder.id}">${folder.path}</option>`)
                .join('');

            const html = `<select id="fm-move-target" class="form-select">`
                + `<option value="">Ana Depo</option>${options}`
                + '</select>';
            const { value } = await Swal.fire({
                title: 'Hedef klasör',
                html,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Taşı',
                cancelButtonText: 'İptal',
                preConfirm: () => {
                    const select = document.getElementById('fm-move-target');
                    return select && select.value !== '' ? Number(select.value) : null;
                }
            });
            if (value === undefined) {
                return;
            }
            const result = await fmRequest('move-selection', {
                folder_ids: selectedFolders,
                file_ids: selectedFiles,
                target_id: value,
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Taşıma başarısız.');
            }
        }

        async function deleteSelected() {
            const selectedFiles = Array.from(state.selection.files);
            const selectedFolders = Array.from(state.selection.folders);
            if (!selectedFiles.length && !selectedFolders.length) {
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
            const result = await fmRequest('bulk-delete', {
                file_ids: selectedFiles,
                folder_ids: selectedFolders,
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Silme işlemi başarısız.');
            }
        }

        async function shareSelected(element) {
            const selection = getSingleSelection();
            if (!selection || selection.type !== 'file') {
                return;
            }
            const currentFile = state.files.find(file => file.id === selection.id);
            if (!currentFile) {
                showToast('error', 'Dosya bulunamadı.');
                return;
            }
            let shareUrl = '';
            if (currentFile.share_token) {
                shareUrl = `${config.baseUrl}/s/${currentFile.share_token}`;
            } else {
                const result = await fmRequest('share-file', { file_id: selection.id });
                if (result.status === 'success' && result.share) {
                    shareUrl = result.share.url;
                    await loadFolder({ folderId: state.folderId, resetPage: false });
                } else {
                    showToast('error', result.message || 'Paylaşım oluşturulamadı.');
                    return;
                }
            }
            await Swal.fire({
                icon: 'success',
                title: 'Paylaşım bağlantısı',
                html: `<div class="text-start">
                        <p class="mb-2">Bağlantıyı kopyalayın:</p>
                        <input type="text" id="fm-share-link" class="form-control bg-dark border-0 text-white" readonly value="${shareUrl}">
                        <button type="button" class="btn btn-gradient mt-3" id="fm-copy-share">Kopyala</button>
                        <p class="small text-white-50 mt-3 mb-0">Bağlantı ${state.settings.share_expiry_minutes || 60} dakika boyunca aktiftir.</p>
                    </div>`,
                didOpen: () => {
                    const copyBtn = document.getElementById('fm-copy-share');
                    const input = document.getElementById('fm-share-link');
                    copyBtn?.addEventListener('click', async () => {
                        try {
                            await navigator.clipboard.writeText(input?.value || shareUrl);
                            copyBtn.textContent = 'Kopyalandı';
                        } catch (error) {
                            copyBtn.textContent = 'Kopyalanamadı';
                        }
                    });
                }
            });
        }

        async function protectFolder() {
            const selection = getSingleSelection();
            if (!selection || selection.type !== 'folder') {
                return;
            }
            if (!state.settings.folder_passwords) {
                showToast('error', 'Klasör şifreleme devre dışı.');
                return;
            }
            const currentFolder = state.folders.find(folder => folder.id === selection.id);
            const { value } = await Swal.fire({
                title: 'Klasör Şifresi',
                input: 'password',
                inputLabel: 'Şifre belirleyin (boş bırakmak kaldırır)',
                inputPlaceholder: 'Boş bırakırsanız şifre kaldırılır',
                inputValue: currentFolder?.is_protected ? '' : '',
                showCancelButton: true,
                confirmButtonText: 'Kaydet',
                cancelButtonText: 'İptal'
            });
            if (value === undefined) {
                return;
            }
            const result = await fmRequest('set-folder-password', {
                folder_id: selection.id,
                password: value || '',
            });
            if (result.status === 'success') {
                showToast('success', result.message);
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Şifre güncellenemedi.');
            }
        }

        async function zipSelected() {
            const selectedFiles = Array.from(state.selection.files);
            const selectedFolders = Array.from(state.selection.folders);
            if (!selectedFiles.length && !selectedFolders.length) {
                showToast('error', 'Zip için öğe seçmelisiniz.');
                return;
            }
            const payload = {
                file_ids: selectedFiles,
                folder_ids: selectedFolders,
                context_folder_id: state.folderId,
            };
            const result = await fmRequest('zip-selection', payload);
            if (result.status === 'success' && result.archive) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Zip hazır',
                    html: `<a class="btn btn-gradient" href="${result.archive.download_url}" target="_blank">Zip dosyasını aç</a>`
                });
                await loadFolder({ folderId: state.folderId, resetPage: false });
            } else {
                showToast('error', result.message || 'Zip oluşturulamadı.');
            }
        }

        function selectAllItems() {
            clearSelection();
            grid.querySelectorAll('.fm-item').forEach(item => addSelection(item));
            updateToolbar();
        }

        function handleKeyShortcuts(event) {
            if (isFormElement(document.activeElement)) {
                return;
            }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a') {
                event.preventDefault();
                selectAllItems();
            }
            if ((event.key === 'Delete' || event.key === 'Backspace') && getSelectionCount() > 0) {
                event.preventDefault();
                deleteSelected();
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
                        uploadZone?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        break;
                    case 'new-file':
                        await createTextFile();
                        break;
                    case 'rename':
                        await renameSelected();
                        break;
                    case 'move':
                        await moveSelected();
                        break;
                    case 'zip':
                        await zipSelected();
                        break;
                    case 'delete':
                        await deleteSelected();
                        break;
                    case 'select-all':
                        selectAllItems();
                        break;
                    default:
                        break;
                }
            });
        });

        if (grid) {
            grid.addEventListener('click', (event) => {
                const item = event.target.closest('.fm-item');
                if (!item || !grid.contains(item)) {
                    return;
                }
                const multiKey = event.ctrlKey || event.metaKey;
                toggleSelection(item, multiKey);
                updateToolbar();
            });

            grid.addEventListener('dblclick', (event) => {
                const item = event.target.closest('.fm-item');
                if (!item || !grid.contains(item)) {
                    return;
                }
                if (item.dataset.type === 'folder') {
                    loadFolder({ folderId: Number(item.dataset.id), resetPage: true });
                } else {
                    openFile(item.dataset.id, item.dataset.name || 'dosya');
                }
            });

            grid.addEventListener('contextmenu', (event) => {
                const item = event.target.closest('.fm-item');
                if (!item || !grid.contains(item)) {
                    return;
                }
                showContextMenu(event, item);
            });
        }

        if (contextMenu) {
            contextMenu.addEventListener('click', async (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }
                const action = target.getAttribute('data-action');
                const selected = getSingleSelection();
                hideContextMenu();
                switch (action) {
                    case 'open':
                        if (selected?.type === 'folder') {
                            loadFolder({ folderId: selected.id, resetPage: true });
                        } else if (selected?.type === 'file') {
                            const file = state.files.find(f => f.id === selected.id);
                            if (file) {
                                openFile(file.id, file.filename);
                            }
                        }
                        break;
                    case 'rename':
                        await renameSelected();
                        break;
                    case 'move':
                        await moveSelected();
                        break;
                    case 'share':
                        await shareSelected(firstSelectedElement());
                        break;
                    case 'protect':
                        await protectFolder();
                        break;
                    case 'zip':
                        await zipSelected();
                        break;
                    case 'delete':
                        await deleteSelected();
                        break;
                    default:
                        break;
                }
            });
        }

        if (paginationWrap) {
            pageButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const direction = button.getAttribute('data-fm-page');
                    let nextPage = state.pagination.page;
                    if (direction === 'prev') {
                        nextPage = Math.max(1, state.pagination.page - 1);
                    } else if (direction === 'next') {
                        nextPage = Math.min(state.pagination.totalPages, state.pagination.page + 1);
                    }
                    if (nextPage !== state.pagination.page) {
                        loadFolder({ folderId: state.folderId, page: nextPage });
                    }
                });
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                const [key, dir] = sortSelect.value.split('|');
                state.sort.key = key || 'name';
                state.sort.direction = dir || 'asc';
                loadFolder({ folderId: state.folderId, resetPage: true });
            });
        }

        document.addEventListener('click', (event) => {
            if (!contextMenu) {
                return;
            }
            if (!contextMenu.hidden && !contextMenu.contains(event.target)) {
                hideContextMenu();
            }
        });
        document.addEventListener('scroll', hideContextMenu);
        document.addEventListener('keydown', handleKeyShortcuts);

        document.addEventListener('upload:completed', () => {
            loadFolder({ folderId: state.folderId, resetPage: false });
        });

        const initialFolder = app.dataset.initialFolder ? Number(app.dataset.initialFolder) : null;
        loadFolder({ folderId: initialFolder, resetPage: true }).catch(error => console.error(error));
    });
})();
