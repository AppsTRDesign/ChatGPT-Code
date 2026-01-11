(function($){
    const toast = (msg,type='success')=> toastr[type](msg);
    const ajax = (url, method='GET', data=null)=> $.ajax({url, method, data, dataType:'json'});

    const page = $('body').data('page');

    // Places
    const placeState = {page:1, per_page:10, q:'', status:''};
    const placeCache = {};
    function placeBadge(status){
        if(status==='1') return '<span class="badge bg-success">Onaylı</span>';
        if(status==='2') return '<span class="badge bg-danger">Reddedildi</span>';
        return '<span class="badge bg-warning text-dark">Beklemede</span>';
    }

    function loadPlaces(){
        const params = Object.assign({}, placeState);
        ajax('api/places.php','GET',params).done(res=>{
            const tbody = $('#places-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(item=>{
                placeCache[item.id] = item;
                const actions = item.status==='0'
                    ? `<div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-success" data-place-status="1" data-id="${item.id}">Onayla</button>
                        <button class="btn btn-danger" data-place-status="2" data-id="${item.id}">Reddet</button>
                       </div>`
                    : `<button class="btn btn-outline-secondary btn-sm" data-place-status="0" data-id="${item.id}">Beklemeye Al</button>`;
                const detail = `<button class="btn btn-outline-primary btn-sm ms-1" data-place-detail="${item.id}">Detay</button>`;
                const remove = `<button class="btn btn-outline-danger btn-sm ms-1" data-place-delete="${item.id}">Sil</button>`;

                tbody.append(`<tr>
                    <td>${item.id}</td>
                    <td>${item.name}</td>
                    <td>${item.city||''}</td>
                    <td>${item.category||''}</td>
                    <td>${placeBadge(item.status)}</td>
                    <td class="text-truncate" style="max-width:180px;">${item.status_note||''}</td>
                    <td>${item.created_at||''}</td>
                    <td>${actions}${detail}${remove}</td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#places-pagination', (p)=>{placeState.page=p;loadPlaces();});
        });
    }

    function renderPagination(meta, target, onClick){
        const $el = $(target);
        $el.empty();
        if (!meta || meta.pages <= 1) return;
        const add = (p,label,disabled=false,active=false)=>{
            const li = $('<li class="page-item"></li>');
            if(disabled) li.addClass('disabled');
            if(active) li.addClass('active');
            const a = $('<a class="page-link" href="#"></a>').text(label).data('page', p);
            li.append(a);
            $el.append(li);
        };
        add(meta.page-1,'«',meta.page<=1);
        for(let i=1;i<=meta.pages;i++){
            add(i,i,false, i===meta.page);
        }
        add(meta.page+1,'»',meta.page>=meta.pages);
        $el.off('click','a').on('click','a',function(e){
            e.preventDefault();
            const p = $(this).data('page');
            if(!p || p<1 || p>meta.pages) return;
            onClick(p);
        });
    }

    function loadStats(){
        ajax('api/stats.php').done(res=>{
            const row = $('#stats-row');
            if(!row.length) return;
            row.empty();
            const cards = [
                {title:'İşletme', value:res.places||0, cls:'primary'},
                {title:'Bekleyen Claim', value:res.pending_claims||0, cls:'warning'},
                {title:'Bekleyen Yorum', value:res.pending_reviews||0, cls:'info'},
                {title:'Kullanıcı', value:res.users||0, cls:'secondary'}
            ];
            cards.forEach(c=>{
                row.append(`<div class="col-sm-6 col-md-3"><div class="card border-${c.cls} shadow-sm"><div class="card-body"><p class="text-muted mb-1">${c.title}</p><h4 class="mb-0">${c.value}</h4></div></div></div>`);
            });
        });
    }

    // Claims
    const claimState = {page:1, per_page:10, q:'', status:''};
    const claimCache = {};
    function loadClaims(){
        const params = Object.assign({}, claimState);
        ajax('api/claims.php','GET',params).done(res=>{
            const tbody = $('#claims-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(item=>{
                claimCache[item.id] = item;
                const actions = item.status==='pending' ? `
                    <button class="btn btn-success btn-sm me-1" data-claim="approve" data-id="${item.id}">Onayla</button>
                    <button class="btn btn-danger btn-sm" data-claim="reject" data-id="${item.id}">Reddet</button>` : '';
                const detail = `<button class="btn btn-outline-primary btn-sm ms-1" data-claim-detail="${item.id}">Detay</button>`;
                tbody.append(`<tr>
                    <td>${item.id}</td>
                    <td>${item.place}</td>
                    <td>${item.user}</td>
                    <td><span class="badge bg-${item.status==='approved'?'success': item.status==='rejected'?'danger':'warning'}">${item.status}</span></td>
                    <td>${item.approval_method}</td>
                    <td>${item.created_at}</td>
                    <td>${actions}${detail}</td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#claims-pagination', (p)=>{claimState.page=p;loadClaims();});
        });
    }

    // Reviews
    const reviewState = {page:1, per_page:10, q:'', status:''};
    const reviewCache = {};
    function loadReviews(){
        const params = Object.assign({}, reviewState);
        ajax('api/reviews.php','GET',params).done(res=>{
            const tbody = $('#reviews-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(r=>{
                reviewCache[r.id] = r;
                const actions = r.status==='pending' ? `
                    <button class="btn btn-success btn-sm me-1" data-review="approve" data-id="${r.id}">Onayla</button>
                    <button class="btn btn-danger btn-sm" data-review="reject" data-id="${r.id}">Reddet</button>` : '';
                const detail = `<button class="btn btn-outline-primary btn-sm ms-1" data-review-detail="${r.id}">Detay</button>`;
                const remove = `<button class="btn btn-outline-danger btn-sm ms-1" data-review-delete="${r.id}">Sil</button>`;
                tbody.append(`<tr>
                    <td>${r.id}</td><td>${r.place}</td><td>${r.author}</td><td>${r.rating}</td><td><span class="badge bg-${r.status==='approved'?'success':r.status==='rejected'?'danger':'warning'}">${r.status}</span></td><td class="text-truncate" style="max-width:260px;">${r.text||''}</td><td>${actions}${detail}${remove}</td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#reviews-pagination', (p)=>{reviewState.page=p;loadReviews();});
        });
    }

    // Users
    const userState = {page:1, per_page:10, q:''};
    function loadUsers(){
        const params = Object.assign({}, userState);
        ajax('api/users.php','GET',params).done(res=>{
            const tbody = $('#users-table tbody');
            if(!tbody.length) return;
            tbody.empty();
            (res.items||[]).forEach(u=>{
                tbody.append(`<tr>
                    <td>${u.id}</td><td>${u.name}</td><td>${u.email}</td>
                    <td><select class="form-select form-select-sm user-role" data-id="${u.id}">
                        ${['admin','business_owner','user'].map(role=>`<option value="${role}" ${role===u.role?'selected':''}>${role}</option>`).join('')}</select></td>
                    <td><select class="form-select form-select-sm user-status" data-id="${u.id}">
                        ${['active','pending','banned'].map(st=>`<option value="${st}" ${st===u.status?'selected':''}>${st}</option>`).join('')}</select></td>
                    <td><button class="btn btn-primary btn-sm" data-user-update="${u.id}">Kaydet</button></td>
                </tr>`);
            });
            renderPagination(res.meta || {page:1,pages:1}, '#users-pagination', (p)=>{userState.page=p;loadUsers();});
        });
    }

    $(document).on('click','[data-claim]',function(){
        const id=$(this).data('id');
        const action=$(this).data('claim');
        ajax('api/claims.php','POST',{action,id}).done(res=>{toast(res.message);claimState.page=1;loadClaims();loadStats();});
    });

    $(document).on('click','[data-review]',function(){
        const id=$(this).data('id');
        const action=$(this).data('review');
        ajax('api/reviews.php','POST',{action,id}).done(res=>{toast(res.message);reviewState.page=1;loadReviews();loadStats();});
    });

    $(document).on('click','[data-review-delete]',function(){
        const id=$(this).data('review-delete');
        if(!confirm('Yorumu silmek istediğinize emin misiniz?')) return;
        ajax('api/reviews.php','POST',{action:'delete',id}).done(res=>{toast(res.message);reviewState.page=1;loadReviews();loadStats();});
    });

    $(document).on('click','[data-place-status]',function(){
        const id=$(this).data('id');
        const status=$(this).data('place-status').toString();
        let note='';
        if(status==='2'){
            note = prompt('Reddetme nedeni');
            if(note===null) return;
        }
        ajax('api/places.php','POST',{id,status,note}).done(res=>{
            toast(res.message);
            placeState.page=1;
            loadPlaces();
            loadStats();
        });
    });

    $(document).on('click','[data-place-delete]',function(){
        const id=$(this).data('place-delete');
        if(!confirm('İşletmeyi silmek istediğinize emin misiniz?')) return;
        ajax('api/places.php','POST',{id,action:'delete'}).done(res=>{
            toast(res.message);
            placeState.page=1;
            loadPlaces();
            loadStats();
        });
    });

    $(document).on('click','[data-user-update]',function(){
        const id=$(this).data('user-update');
        const role=$(`select.user-role[data-id=${id}]`).val();
        const status=$(`select.user-status[data-id=${id}]`).val();
        ajax('api/users.php','POST',{id,role,status}).done(res=>{toast(res.message);});
    });

    $('#claim-filters').on('submit',function(e){
        e.preventDefault();
        claimState.q = $(this).find('[name=q]').val();
        claimState.status = $(this).find('[name=status]').val();
        claimState.per_page = $(this).find('[name=per_page]').val();
        claimState.page = 1;
        loadClaims();
    });

    $('#review-filters').on('submit',function(e){
        e.preventDefault();
        reviewState.q = $(this).find('[name=q]').val();
        reviewState.status = $(this).find('[name=status]').val();
        reviewState.per_page = $(this).find('[name=per_page]').val();
        reviewState.page = 1;
        loadReviews();
    });

    $('#place-filters').on('submit',function(e){
        e.preventDefault();
        placeState.q = $(this).find('[name=q]').val();
        placeState.status = $(this).find('[name=status]').val();
        placeState.per_page = $(this).find('[name=per_page]').val();
        placeState.page = 1;
        loadPlaces();
    });

    $('#user-filters').on('submit',function(e){
        e.preventDefault();
        userState.q = $(this).find('[name=q]').val();
        userState.per_page = $(this).find('[name=per_page]').val();
        userState.page = 1;
        loadUsers();
    });

    if(page==='dashboard'){ loadStats(); }
    if(page==='claims'){ loadClaims(); loadStats(); }
    if(page==='reviews'){ loadReviews(); loadStats(); }
    if(page==='users'){ loadUsers(); loadStats(); }
    if(page==='places'){ loadPlaces(); loadStats(); }

    $(document).on('click','[data-claim-detail]',function(){
        const id=$(this).data('claim-detail');
        const claim = claimCache[id];
        if(!claim) return;
        const payload = claim.approval_payload || {};
        const payloadEntries = typeof payload === 'object' ? Object.entries(payload) : [];
        const payloadHtml = payloadEntries.length
            ? payloadEntries.map(([k,v])=>{
                const val = typeof v === 'string' && v.startsWith('http')
                    ? `<a href="${v}" target="_blank" rel="noopener">${v}</a>`
                    : v;
                return `<div class="mb-1"><strong>${k}:</strong> ${val}</div>`;
            }).join('')
            : (payload ? `<div>${payload}</div>` : '');
        $('#claimDetailModalLabel').text(`Talep #${claim.id}`);
        $('#claim-detail-place').text(claim.place);
        $('#claim-detail-user').text(claim.user);
        $('#claim-detail-method').text(claim.approval_method);
        $('#claim-detail-status').text(claim.status);
        $('#claim-detail-verified').text(claim.verified_at || '-');
        $('#claim-detail-payload').html(payloadHtml || '<em>Detay yok</em>');
        const modal = new bootstrap.Modal(document.getElementById('claimDetailModal'));
        modal.show();
    });

    $(document).on('click','[data-review-detail]',function(){
        const id=$(this).data('review-detail');
        const review = reviewCache[id];
        if(!review) return;
        $('#reviewDetailModalLabel').text(`Yorum #${review.id}`);
        $('#review-detail-place').text(review.place);
        $('#review-detail-author').text(review.author);
        $('#review-detail-rating').text(review.rating);
        $('#review-detail-status').text(review.status);
        $('#review-detail-date').text(review.created_at || '-');
        $('#review-detail-text').text(review.text || '');
        const extras = review.text_extra || {};
        const extrasHtml = Object.keys(extras).length
            ? Object.entries(extras).map(([k,v])=>`<div class="review-extra-item"><strong>${k}</strong>: ${v}</div>`).join('')
            : '<em>Ek bilgi yok</em>';
        $('#review-detail-extra').html(extrasHtml);
        const photos = review.review_photo_urls || [];
        const photoHtml = photos.length
            ? photos.map(url=>`<img src="${url}" alt="Review photo" class="review-photo-thumb">`).join('')
            : '<em>Fotoğraf yok</em>';
        $('#review-detail-photos').html(photoHtml);
        const modal = new bootstrap.Modal(document.getElementById('reviewDetailModal'));
        modal.show();
    });

    $(document).on('click','[data-place-detail]',function(){
        const id=$(this).data('place-detail');
        const place = placeCache[id];
        if(!place) return;
        $('#placeDetailModalLabel').text(`İşletme #${place.id}`);
        $('#place-detail-name').text(place.name || '');
        $('#place-detail-phone').text(place.phone || '-');
        $('#place-detail-city').text(place.city || '');
        $('#place-detail-category').text(place.category || '');
        $('#place-detail-description').text(place.description || '');
        const img = place.business_image || '';
        $('#place-detail-image').attr('src', img || '').toggleClass('d-none', !img);
        const modal = new bootstrap.Modal(document.getElementById('placeDetailModal'));
        modal.show();
    });
})(jQuery);
